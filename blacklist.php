<?php
require_once "scs_header.php";
$database->blacklist->install();
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH'])) && ($_SERVER['REQUEST_METHOD'] == "POST")) {
    $results = array();
    $bl_page   = max(1, intval($_POST['page'] ?? 1));
    $bl_search = trim($_POST['search'] ?? '');
    switch ($_POST['action']) {
      case "list":
        break;
      case "add":
        $domain = strtolower(trim($_POST['domain']));
        $comment = trim($_POST['comment']);
        if (!strlen($domain)) {
            $results['blacklist_message'] = "<span style='color:red;'>Domain is required</span>";
        } elseif (!preg_match('/^(?:[a-z0-9\-]+\.)+[a-z0-9\-]{2,63}$/i', $domain)) {
            $results['blacklist_message'] = "<span style='color:red;'>Invalid domain format</span>";
        } else {
            $database->blacklist->read($domain, "domain");
            if ($database->blacklist->meta->rows) {
                $results['blacklist_message'] = "<span style='color:red;'>" . htmlspecialchars($domain) . " is already blacklisted</span>";
            } else {
                $database->blacklist->data = new blacklist_data_class();
                $database->blacklist->data->domain = $domain;
                $database->blacklist->data->comment = $comment;
                $database->blacklist->update(FALSE);
                if ($database->blacklist->meta->error) {
                    $results['blacklist_message'] = "<span style='color:red;'>Database error</span>";
                } else {
                    $options = array();
                    $options['comment'] = "Domain blacklisted: " . $domain;
                    $database->log->update("Blacklist:Add", $options);
                    $results['blacklist_message'] = htmlspecialchars($domain) . " has been blacklisted";
                    $bl_page = 1;
                }
            }
        }
        break;
      case "remove":
        $id = intval($_POST['id']);
        $database->blacklist->read($id);
        if ($database->blacklist->meta->rows) {
            $domain = $database->blacklist->data->domain;
            $database->blacklist->delete($id);
            $options = array();
            $options['comment'] = "Domain removed from blacklist: " . $domain;
            $database->log->update("Blacklist:Remove", $options);
            $results['blacklist_message'] = htmlspecialchars($domain) . " has been removed from the blacklist";
            $bl_page = 1;
        }
        break;
    }
    $results['blacklist_content'] = blacklist_output($bl_page, $bl_search);
    die(json_encode($results));
}
$database->user->access("Administrator");
$forms->title("Domain Blacklist");
$menu->head();
print $forms->message();
print "<style>#scs_content { min-height: calc(100vh - 200px); }</style>";
?>
<script>
var blacklist_search_timer;

function blacklist_load(page) {
    var search = jQuery("#blacklist_search").val().trim();
    jQuery.ajax({
        type: "post",
        dataType: "json",
        data: { action: "list", page: page, search: search },
        success: function(response) {
            jQuery("#blacklist_content").html(response['blacklist_content']);
        },
        error: function(xhr) {
            console.log(xhr);
        }
    });
}

function blacklist_search_keyup() {
    clearTimeout(blacklist_search_timer);
    blacklist_search_timer = setTimeout(function() { blacklist_load(1); }, 300);
}

function blacklist_add() {
    var domain = jQuery("#new_domain").val().trim();
    if (!domain) return;
    if (!confirm("Blacklist domain: " + domain + "?")) return;
    jQuery.ajax({
        type: "post",
        dataType: "json",
        data: { action: "add", domain: domain, comment: jQuery("#new_comment").val(), page: 1, search: jQuery("#blacklist_search").val().trim() },
        success: function(response) {
            jQuery("#blacklist_message").html(response['blacklist_message']);
            jQuery("#blacklist_content").html(response['blacklist_content']);
            jQuery("#new_domain").val("");
            jQuery("#new_comment").val("");
        },
        error: function(xhr) {
            console.log(xhr);
        }
    });
}

function blacklist_remove(id, domain) {
    if (!confirm("Remove " + domain + " from the blacklist?")) return;
    jQuery.ajax({
        type: "post",
        dataType: "json",
        data: { action: "remove", id: id, page: 1, search: jQuery("#blacklist_search").val().trim() },
        success: function(response) {
            jQuery("#blacklist_message").html(response['blacklist_message']);
            jQuery("#blacklist_content").html(response['blacklist_content']);
        },
        error: function(xhr) {
            console.log(xhr);
        }
    });
}
</script>
<?php
print $forms->open();
$results = array();
$results[] = "<table class='standard border'>";
$results[] = "<tr>";
$results[] = "<td class='scscpq_label'>Domain:</td>";
$results[] = "<td>" . $forms->text("new_domain", "", 40, 80, "", array("class" => "scscpq_input", "placeholder" => "example.com")) . "</td>";
$results[] = "<td class='scscpq_label'>Reason:</td>";
$results[] = "<td>" . $forms->text("new_comment", "", 40, 200, "", array("class" => "scscpq_input", "placeholder" => "Optional")) . "</td>";
$results[] = "<td>" . $forms->button("Add to Blacklist", array("onclick" => "blacklist_add();", "class" => "red-button", "style" => "margin-top:0;padding:10px 20px;")) . "</td>";
$results[] = "</tr>";
$results[] = "</table>";
print implode("", $results);
print "<div style='padding:10px 0;'>";
print "<label style='margin-right:8px;font-weight:bold;'>Search:</label>";
print "<input type=text name='blacklist_search' id='blacklist_search' size='30' maxlength='200' class='scscpq_input' placeholder='Filter by domain or reason...' onkeyup='blacklist_search_keyup();' style='width:auto;'>";
print "</div>";
print "<div id=blacklist_message style='padding:10px;'></div>";
print "<div id=blacklist_content>" . blacklist_output() . "</div>";
print $forms->close();
$menu->copyright();

function blacklist_output($page=1, $search='') {
    global $database, $forms;
    $database->query_limit = 10;
    $query = array("select * from blacklist");
    if (strlen($search)) {
        $query[] = "where domain like " . fn_escape('%' . $search . '%') . " or comment like " . fn_escape('%' . $search . '%');
    }
    $query[] = "order by domain";
    $query[] = $database->page_limit($page);
    $database->blacklist->query($query, TRUE);
    $results = array();
    $results[] = "<table class='standard border tablesorter'>";
    $results[] = "<thead>";
    $results[] = "<tr>";
    $results[] = "<th width='10%'>Action</th>";
    $results[] = "<th width='35%'>Domain</th>";
    $results[] = "<th width='35%'>Reason</th>";
    $results[] = "<th width='20%'>Date Added</th>";
    $results[] = "</tr>";
    $results[] = "</thead>";
    $results[] = "<tbody>";
    if (!$database->blacklist->meta->rows) {
        $results[] = "<tr><td colspan=4>" . (strlen($search) ? "No results for &ldquo;" . htmlspecialchars($search) . "&rdquo;" : "No blacklisted domains") . "</td></tr>";
    } else {
        while ($database->blacklist->fetch = $database->blacklist->fetch_array()) {
            $database->blacklist->fetch();
            $results[] = "<tr>";
            $results[] = "<td class=center>" . $forms->button("Remove", array("onclick" => "blacklist_remove(" . $database->blacklist->data->id . ",'" . addslashes($database->blacklist->data->domain) . "');", "style" => "padding:5px 10px;cursor:pointer;")) . "</td>";
            $results[] = "<td>" . htmlspecialchars($database->blacklist->data->domain) . "</td>";
            $results[] = "<td>" . htmlspecialchars($database->blacklist->data->comment) . "</td>";
            $results[] = "<td class=center>" . ($database->blacklist->data->created ? date("m/d/Y h:i A", $database->blacklist->data->created) : "") . "</td>";
            $results[] = "</tr>";
        }
    }
    $database->blacklist->free_result();
    $results[] = "</tbody>";
    $results[] = "</table>";
    $page_break = new page_item_break($database->blacklist->meta->found_rows, "blacklist_load(%page%);");
    $results[] = $page_break->page($page);
    return implode("", $results);
}
?>
