<?php
require_once "scs_header.php";
$database->user->access("Administrator");
$obj=new local_class();

class local_class {
	function scs_table_version() {
		$results=array();
        $results['12/19/2023']="Multiple Subcategories";
        $results['10/26/2022']="Initial release";
        $results['file']=__FILE__;
        return $results;
    }
	function __construct() {
        $this->action=array(""=>"Select Increase Type","fixed"=>"Fixed Amount","percent"=>"Percent");
        if (preg_match("/^xmlhttprequest$/i",$_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->json();
            } else {
            $this->html();
        }
    }
    function json() {
        $this->response=new stdClass();
        switch ($_POST['action']) {
          case "detail":
            $this->response->detail_results=$this->json_detail();
            break;
        }
        die(json_encode($this->response));
    }
    function input() {
        global $database, $forms;
        $this->input=new StdClass();
        $this->input->action=$_POST['action'];
        $this->input->subcategory=array();
        switch ($this->input->action) {
          case "":
            return;
          case "percent":
            $this->input->percent=fn_number($_POST['percent'],2);
            if ( ($this->input->percent <= 0) || ($this->input->percent >= 100) ) $forms->error("percent","Must be > 0.00% and < 100.00%");
            break;
          default:
            $this->input->fixed=fn_number($_POST['fixed'],2);
            if ($this->input->fixed <= 0) $forms->error("fixed","Must be > $0.00");
        }
        foreach ($_POST as $key => $value) {
            $field=explode("_",$key);
            if ($field[0] == "subcategory") $this->input->subcategory[]=$field[1];
        }
        if (!sizeof($this->input->subcategory)) $forms->error("subcategory_id","No subcategories selected");
        if (sizeof($forms->error)) return;

        set_time_limit(300);
        $this->stopwatch=new stopwatch_class("Price Increase");
        $this->log_type=array("Import Table");
        $this->log_type[]="Price Increase: " . $this->action[$this->input->action];
        $this->log_comment=array();
        $this->log_comment[]="Subcategories: " . number_format(sizeof($this->input->subcategory));
        $this->log_comment[]="Increase: " . ( ($this->input->action == "percent") ? number_format($this->input->percent,2) . "%" : "$" . number_format($this->input->fixed,2) );
        $this->log_comment['error']="Update failed";
        $options=array();
        $options['comment']=$this->log_comment;
        $this->log_id=$database->log->update($this->log_type,$options);

        $this->timestamp=strtotime("now");
        $this->count_update=0;
        $this->count_skip=0;
        foreach ($this->input->subcategory as $subcategory_id) {
            $database->transaction("start");
            $query_where=array();
            $query_where[]=new query_where("subcategory_id","=",$subcategory_id);
            $query=array("select sku from inventory");
            $query[]=$database->where($query_where);
            $database->temp->query($query);
            while ($database->temp->fetch = $database->temp->fetch_array()) {
                $database->inventory->read($database->temp->fetch['sku'],"sku");
                if (!$database->inventory->data->pricing[0]->price) {
                    $this->count_skip++;
                    continue;
                }
                foreach ($database->inventory->data->pricing as $obj) {
                    if ($this->input->action == "percent") {
                        $obj->price += round($obj->price * ($this->input->percent/100),2);
                      } else {
                        $obj->price += $this->input->fixed;
                    }
                }
                $database->inventory->update(TRUE);
                $this->count_update++;
            }
            $database->subcategory->read($subcategory_id);
            $database->subcategory->data->price_increase=$this->timestamp;
            $database->subcategory->update(TRUE);
            $database->transaction("commit");
        }
        $this->stopwatch->stop();
        unset($this->log_comment['error']);
        $this->log_comment[]="SKUs updated: " . number_format($this->count_update) . " SKUs skipped: " . number_format($this->count_skip);
        $this->log_comment[]="Elapsed time: " . number_format($this->stopwatch->elapsed,2) . " seconds";
        $options=array();
        $options['comment']=$this->log_comment;
        $options['id']=$this->log_id;
        $this->log_id=$database->log->update($this->log_type,$options);
        $forms->message[]="<br>" . implode("<br>",$this->log_comment);
    }
    function html() {
        global $database, $forms, $menu;
        $forms->title("Inventory Price Increase");
        $forms->message[]="Revised " . key($this->scs_table_version());
        $this->input();
        $menu->head();
        print $forms->message();
        print $this->js();
        print $forms->open();
        print "<p class=large><b>Price increase cannot be rolled back.</b></p>";

        $query_where=array();
        $query_where[]=new query_where("subcategory.output","<>","smart");
        $query_where[]=new query_where("subcategory.status","=","active");
        $query_where[]=new query_where("category.id","<>",0);
        $query_where[]=new query_where("subcategory.id","<>",0);
        $query=array("select");
        $query[]="category.id as 'category.id',";
        $query[]="category.name as 'category.name',";
        $query[]="subcategory.id as 'subcategory.id',";
        $query[]="subcategory.name as 'subcategory.name',";
        $query[]="subcategory.price_increase as 'subcategory.price_increase',";
        $query[]="count(*) as 'count'";
        $query[]="from inventory";
        $query[]="left join category on category.id=inventory.category_id";
        $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
        $query[]=$database->where($query_where);
        $query[]="group by category.id,subcategory.id";
        $query[]="order by category.name, subcategory.name";
        $database->temp->query($query);
        $category=array();
        while ($database->temp->fetch = $database->temp->fetch_array()) {
            $category_id=$database->temp->fetch['category.id'];
            if (!array_key_exists($category_id, $category)) {
                $obj=new stdClass();
                $obj->id=$category_id;
                $obj->name=$database->temp->fetch['category.name'];
                $obj->subcategory=array();
                $category[$category_id]=$obj;
            } else {
                $obj=$category[$category_id];
            }
            $item=new stdClass();
            $item->id=$database->temp->fetch['subcategory.id'];
            $item->name=$database->temp->fetch['subcategory.name'];
            $item->price_increase=$database->temp->fetch['subcategory.price_increase'];
            $item->count=$database->temp->fetch['count'];
            $item->checked=( (sizeof($forms->error)) && (in_array($item->id, $this->input->subcategory)) ) ? 1 : 0;
            $obj->subcategory[]=$item;
        }
        print "<table class='border'>";
        print "<tr>";
        print "<th width='20%'>Category</th>";
        print "<th width='40%'>Subcategory</th>";
        print "<th width='10%'># SKUs</th>";
        print "<th width='40%'>Last Price Increase</th>";
        print "</tr>";
        print "<tr>";
        print "<td colspan=4>" . $forms->checkbox("all",1,0,array("onclick"=>"jQuery('.subcategory_all').prop('checked',this.checked);")) . " All categories/subcategories?</td>";
        print "</tr>";
        foreach ($category as $obj) {
            $category_field="category_" . $obj->id;
            foreach ($obj->subcategory as $row => $item) {
                $subcategory_field="subcategory_" . $item->id;
                print $forms->hidden("count_{$subcategory_field}",$item->count,"","count");
                print "<tr>";
                if (!$row) print "<td rowspan=" . sizeof($obj->subcategory) . ">" . $forms->checkbox($category_field, 1, 0,array("class"=>"subcategory_all", "onclick"=>"select_group(" . fn_escape($category_field) . ",this.checked);")) . " " . $obj->name . "</td>";
                print "<td>" . $forms->checkbox($subcategory_field,1,$item->checked,array("class"=>"subcategory_all {$category_field} input", "onclick"=>"select_count();")) . $item->name . "</td>";
                print "<td class=right>" . number_format($item->count) . "</td>";
                print "<td>" . (($item->price_increase) ? date("m/d/Y h:i A", $item->price_increase) : "Never") . "</td>";
                print "</tr>";
            }
        }
        print "<tr>";
        print "<td>". $forms->select("action",$this->action,$this->input->action,FALSE,array("onchange"=>"utility_screen();")) . "</td>";
        $text=array();
        $text[]="<span class=percent_class>" . $forms->text("percent", $this->input->percent, 6, 6, "decimal:2") . "%</span>";
        if ($forms->error_text['percent']) $text[]="<span class=percent_class>" . $forms->error_text['percent'] . "</span>";
        $text[]="<span class=fixed_class>$" . $forms->text("fixed", $this->input->fixed, 8, 8, "decimal:2") . "</span>";
        if ($forms->error_text['fixed']) $text[]="<span class=fixed_class>" . $forms->error_text['fixed'] . "</span>";
        print "<td colspan=3>" . implode("",$text) . "</td>";
        print "</tr>";
        print "</table>";
        print "<p>" . $forms->button("Update Price",array("id"=>"button_id","onclick"=>"fn_action();")) . "</p>";
        print $forms->close();
        $menu->copyright();
    }
    function js() {
        return <<<EOT

<script>
jQuery(document).ready(function() {
    utility_screen();
});
function select_group(group, checked) {
    jQuery("." + group).prop('checked',checked);
    select_count();
}
function utility_screen() {
    jQuery(".fixed_class").hide();
    jQuery(".percent_class").hide();
    switch (jQuery("#action").val()) {
        case "percent":
        jQuery(".percent_class").show();
        break;
        case "fixed":
        jQuery(".fixed_class").show();
        break;
    }
    select_count();
}
function select_count() {
    total=0;
    count=scscpq_class_data('count');
    jQuery('.input').each(function() {
        if (this.checked) total = total + parseInt(count["count_" + this.id]);
    });
    jQuery("#button_id").val((total) ? "Update Price: " + total.toLocaleString() + " SKUs" : "No records selected");
    if ( (total) && (jQuery("#action")[0].selectedIndex) ) {
        jQuery("#button_id").prop("disabled",false);
        } else {
        jQuery("#button_id").prop("disabled",true);
    }
}
function fn_action() {
    if (!confirm("I understand that price increases cannot be rolled back.")) return;
    jQuery("#button_div").html("Working ...");
    jQuery("#scs_form").submit();
}
</script>
EOT;
    }
}
?>