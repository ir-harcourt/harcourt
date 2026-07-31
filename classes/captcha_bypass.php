<?php
$database->captcha_bypass = new captcha_bypass_class();
class captcha_bypass_class extends database_class {
    function scs_table_version() {
        $results = array();
        $results['07/30/2026'] = "Initial release";
        $results['file'] = __FILE__;
        return $results;
    }
    function __construct() {
        $this->data = new captcha_bypass_data_class();
        $this->fetch = array();
        $this->meta = new database_meta_class();
    }
    function fetch($fetch = FALSE) {
        if ($fetch) $this->fetch = $this->fetch_array();
        $this->data = new captcha_bypass_data_class();
        $this->data->id = $this->fetch['id'];
        $this->data->email = $this->fetch['email'];
        $this->data->comment = $this->fetch['comment'];
        $this->data->created = $this->fetch['created'];
    }
    function read($id, $field = "id") {
        $query = array("select * from captcha_bypass");
        $query[] = "where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
            $this->fetch(TRUE);
            $this->free_result();
        } else {
            $this->data = new captcha_bypass_data_class();
        }
    }
    function update($update = FALSE) {
        $fields = array();
        $fields[] = "id=" . fn_escape($this->data->id, FALSE);
        $fields[] = "email=" . fn_escape($this->data->email);
        $fields[] = "comment=" . fn_escape($this->data->comment, "null");
        $fields[] = "created=" . fn_escape($this->data->created, FALSE);
        $query = array();
        if ($update) {
            $query[] = "update captcha_bypass set";
            $query[] = implode(",\n", $fields);
            $query[] = "where id=" . fn_escape($this->data->id);
        } else {
            $query[] = "insert into captcha_bypass set";
            $query[] = implode(",\n", $fields);
        }
        $this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) {
            $this->data->id = $this->insert_id();
        }
    }
    function delete($id) {
        $query = array("delete from captcha_bypass");
        $query[] = "where id=" . fn_escape($id);
        $this->query($query);
    }
    function check($email) {
        if (!strlen($email)) return FALSE;
        if (!$this->table_exists()) return FALSE;
        $email = strtolower(trim($email));
        $query = array("select id from captcha_bypass");
        $query[] = "where email=" . fn_escape($email);
        $query[] = "limit 1";
        $this->query($query);
        $found = ($this->meta->rows > 0);
        $this->free_result();
        return $found;
    }
    function table_exists() {
        $this->query("SHOW TABLES LIKE 'captcha_bypass'");
        $exists = ($this->meta->rows > 0);
        $this->free_result();
        return $exists;
    }
}
class captcha_bypass_data_class {
    var $id = 0;
    var $email;
    var $comment;
    var $created = 0;
    function __construct() {
        $this->created = strtotime("now");
    }
}
?>
