<?php
require_once "scs_header.php";
$database->user->access("Administrator");
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH'])) && ($_SERVER['REQUEST_METHOD'] == "POST")) {
    $results = array();
    $company_name = trim($_POST['company_name']);
    switch ($_POST['action']) {
      case "load":
        $results['message'] = "&nbsp;";
        $results['content'] = "&nbsp;";
        if (!strlen($company_name)) {
            $results['message'] = "<span style='color:red;'>Company name is required</span>";
            break;
        }
        if (!design_partner_agreement_company_exists($company_name)) {
            $results['message'] = "<span style='color:red;'>No users found for company &ldquo;" . htmlspecialchars($company_name) . "&rdquo;</span>";
            break;
        }
        $results['content'] = design_partner_agreement_output($company_name);
        break;
      case "save":
        if (!strlen($company_name)) {
            $results['message'] = "<span style='color:red;'>Company name is required</span>";
            $results['status'] = "error";
            break;
        }
        design_partner_agreement_save($company_name);
        $options = array();
        $options['comment'] = "Design Partner Agreement categories updated: " . $company_name;
        $database->log->update("DesignPartnerAgreement:Update", $options);
        $results['message'] = htmlspecialchars($company_name) . " categories updated";
        $results['status'] = "success";
        $results['content'] = design_partner_agreement_output($company_name);
        break;
    }
    die(json_encode($results));
}
$forms->title("Design Partner Agreement");
$menu->head();
print $forms->message();
?>
<style>
#dpa_message.dpa-message-success { background-color:#d4edda; color:#155724; padding:8px 10px; border-radius:4px; }
#dpa_message.dpa-message-error { background-color:#f8d7da; color:#721c24; padding:8px 10px; border-radius:4px; }
ul.ui-autocomplete { max-height:220px !important; overflow-y:auto !important; overflow-x:hidden !important; }
</style>
<script>
function design_partner_agreement_flash_message(status) {
    var el = jQuery("#dpa_message");
    el.stop(true, true).show();
    el.removeClass("dpa-message-success dpa-message-error");
    jQuery(document).off(".dpaMessage");
    if (status != "success" && status != "error") return;
    el.addClass(status == "success" ? "dpa-message-success" : "dpa-message-error");
    var shown_at = Date.now();
    var min_visible_ms = 1200;
    jQuery(document).on("mousemove.dpaMessage click.dpaMessage keydown.dpaMessage scroll.dpaMessage", function() {
        if (Date.now() - shown_at < min_visible_ms) return;
        jQuery(document).off(".dpaMessage");
        el.fadeOut(600, function() {
            el.html("&nbsp;").removeClass("dpa-message-success dpa-message-error").show();
        });
    });
}
function design_partner_agreement_load() {
    var company = jQuery("#dpa_company_name").val().trim();
    jQuery.ajax({
        type: "post",
        dataType: "json",
        data: { action: "load", company_name: company },
        success: function(response) {
            jQuery("#dpa_message").html(response['message']);
            jQuery("#dpa_content").html(response['content']);
            scscpq_ipc("scscpq_wp_iframe", "resize", jQuery("#scs_content").height());
        },
        error: function(xhr) {
            console.log(xhr);
        }
    });
}
function design_partner_agreement_save() {
    var data = { action: "save", company_name: jQuery("#dpa_company_name").val().trim() };
    jQuery("#dpa_content input[type=checkbox]").each(function() {
        data[jQuery(this).attr("name")] = jQuery(this).is(":checked") ? 1 : 0;
    });
    jQuery.ajax({
        type: "post",
        dataType: "json",
        data: data,
        success: function(response) {
            jQuery("#dpa_message").html(response['message']);
            design_partner_agreement_flash_message(response['status']);
            jQuery("#dpa_content").html(response['content']);
            scscpq_ipc("scscpq_wp_iframe", "resize", jQuery("#scs_content").height());
        },
        error: function(xhr) {
            console.log(xhr);
        }
    });
}
jQuery(function() {
    jQuery('#dpa_company_name').autocomplete({
        source: '/scs_ajax.php?table=user&source=company_name',
        minLength: 2,
        select: function(event, ui) {
            jQuery('#dpa_company_name').val(ui.item.value);
            design_partner_agreement_load();
            return false;
        },
        html: true,
        open: function(event, ui) {
            jQuery('.ui-autocomplete').css('z-index', 1000);
            scscpq_ipc("scscpq_wp_iframe", "resize", document.body.scrollHeight);
        },
        close: function(event, ui) {
            scscpq_ipc("scscpq_wp_iframe", "resize", jQuery("#scs_content").height());
        }
    });
});
</script>
<?php
print $forms->open();
print "<p>Company Name: " . $forms->text("dpa_company_name", "", 40, 100) . " " . $forms->button("Load", array("onclick" => "design_partner_agreement_load();")) . "</p>";
print "<div id=dpa_message style='padding:10px 0;'></div>";
print "<div id=dpa_content></div>";
print $forms->close();
$menu->copyright();

function design_partner_agreement_company_exists($company_name) {
    global $database;
    $query_where = array();
    $query_where[] = new query_where("company_name", "=", $company_name);
    $query = array("select id from user");
    $query[] = $database->where($query_where);
    $query[] = "limit 1";
    $database->user->query($query);
    return $database->user->meta->rows ? TRUE : FALSE;
}

function design_partner_agreement_save($company_name) {
    global $database;
    $database->temp->query("DELETE FROM company_category_block WHERE company_name=" . fn_escape($company_name));
    $database->category->query("SELECT id FROM category WHERE active=1");
    while ($database->category->fetch = $database->category->fetch_array()) {
        $database->category->fetch();
        if ($_POST["company_category_" . $database->category->data->id]) {
            $database->temp->query("INSERT INTO company_category_block (company_name, category_id) VALUES (" . fn_escape($company_name) . "," . fn_escape($database->category->data->id, FALSE) . ")");
        }
    }
    $database->category->free_result();
}

function design_partner_agreement_output($company_name) {
    global $database, $forms;
    $blocked_categories = array();
    $database->temp->query("SELECT category_id FROM company_category_block WHERE company_name=" . fn_escape($company_name));
    while ($database->temp->fetch = $database->temp->fetch_array()) {
        $blocked_categories[] = $database->temp->fetch['category_id'];
    }
    $database->temp->free_result();
    $results = array();
    $results[] = "<table class='standard noborder'>";
    $results[] = "<tr><td colspan=2>Check to restrict " . htmlspecialchars($company_name) . "'s access to a category (Design Partner Agreement required)</td></tr>";
    $database->category->query("SELECT * FROM category WHERE active=1 ORDER BY sort_order,name");
    while ($database->category->fetch = $database->category->fetch_array()) {
        $database->category->fetch();
        $checked = in_array($database->category->data->id, $blocked_categories) ? 1 : 0;
        $results[] = "<tr>";
        $results[] = "<td><label style='display:flex;align-items:center;gap:8px;cursor:pointer;'>" . $forms->checkbox("company_category_" . $database->category->data->id, 1, $checked) . $database->category->data->name . "</label></td>";
        $results[] = "</tr>";
    }
    $database->category->free_result();
    $results[] = "</table>";
    $results[] = "<p>" . $forms->button("Save", array("onclick" => "design_partner_agreement_save();")) . "</p>";
    return implode("", $results);
}
?>
