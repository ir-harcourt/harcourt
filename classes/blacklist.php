<?php
$database->blacklist = new blacklist_class();
class blacklist_class extends database_class {
    function scs_table_version() {
        $results = array();
        $results['06/22/2026'] = "Initial release";
        $results['file'] = __FILE__;
        return $results;
    }
    function __construct() {
        $this->data = new blacklist_data_class();
        $this->fetch = array();
        $this->meta = new database_meta_class();
    }
    function fetch($fetch = FALSE) {
        if ($fetch) $this->fetch = $this->fetch_array();
        $this->data = new blacklist_data_class();
        $this->data->id = $this->fetch['id'];
        $this->data->domain = $this->fetch['domain'];
        $this->data->comment = $this->fetch['comment'];
        $this->data->created = $this->fetch['created'];
    }
    function read($id, $field = "id") {
        $query = array("select * from blacklist");
        $query[] = "where {$field}=" . fn_escape($id);
        $this->query($query);
        if ($this->meta->rows) {
            $this->fetch(TRUE);
            $this->free_result();
        } else {
            $this->data = new blacklist_data_class();
        }
    }
    function update($update = FALSE) {
        $fields = array();
        $fields[] = "id=" . fn_escape($this->data->id, FALSE);
        $fields[] = "domain=" . fn_escape($this->data->domain);
        $fields[] = "comment=" . fn_escape($this->data->comment, "null");
        $fields[] = "created=" . fn_escape($this->data->created, FALSE);
        $query = array();
        if ($update) {
            $query[] = "update blacklist set";
            $query[] = implode(",\n", $fields);
            $query[] = "where id=" . fn_escape($this->data->id);
        } else {
            $query[] = "insert into blacklist set";
            $query[] = implode(",\n", $fields);
        }
        $this->query($query);
        if ((!$this->data->id) && (!$this->meta->error)) {
            $this->data->id = $this->insert_id();
        }
    }
    function delete($id) {
        $query = array("delete from blacklist");
        $query[] = "where id=" . fn_escape($id);
        $this->query($query);
    }
    function check($email) {
        if (!strlen($email)) return FALSE;
        $parts = explode("@", $email, 2);
        if (!isset($parts[1]) || !strlen($parts[1])) return FALSE;
        if (!$this->table_exists()) return FALSE;
        $domain = strtolower(trim($parts[1]));
        $query = array("select id from blacklist");
        $query[] = "where domain=" . fn_escape($domain);
        $query[] = "limit 1";
        $this->query($query);
        $found = ($this->meta->rows > 0);
        $this->free_result();
        return $found;
    }
    function table_exists() {
        $this->query("SHOW TABLES LIKE 'blacklist'");
        $exists = ($this->meta->rows > 0);
        $this->free_result();
        return $exists;
    }
    function install() {
        if ($this->table_exists()) return;
        $query = array();
        $query[] = "CREATE TABLE `blacklist` (";
        $query[] = "`id` int(11) NOT NULL AUTO_INCREMENT,";
        $query[] = "`domain` varchar(80) NOT NULL,";
        $query[] = "`comment` varchar(200) DEFAULT NULL,";
        $query[] = "`created` int(10) unsigned DEFAULT '0',";
        $query[] = "PRIMARY KEY (`id`),";
        $query[] = "UNIQUE KEY `domain` (`domain`)";
        $query[] = ") ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Domain blacklist (06/22/2026)'";
        $this->query(implode("\n", $query));
    }
}
class blacklist_data_class {
    var $id = 0;
    var $domain;
    var $comment;
    var $created = 0;
    function __construct() {
        $this->created = strtotime("now");
    }
}
/*
CREATE TABLE `blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` varchar(80) NOT NULL,
  `comment` varchar(200) DEFAULT NULL,
  `created` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Domain blacklist (06/22/2026)'
*/
?>
