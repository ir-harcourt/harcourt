<?php
/* Global configure functions */
function scsps_functions_version() {
    $results=array();
    $results['11/08/2024']="Initial release: configure_map, configure_query, configure_field";
    return $results;
}
function configure_map($obj, $field, $map) {
    if (!array_key_exists($field, $map)) {
        $obj->type="uppercase";
      } else {
        $obj->type=$map[$field];
        $options=explode(":", $map[$field]);
        switch ($options[0]) {
          case "decimal":
            $obj->decimal=intval($options[1]);
            break;
          case "fixed":
            $obj->fixed=intval($options[1]);
            break;
        }
    }
}
function configure_query($fields, $data) {
    if (!sizeof($fields)) return;
    $query=array();
    foreach ($fields as $field => $map) {
        $query[$field]=configure_field($data->$field, $map);
    }
    return http_build_query($query);
}
function configure_field($text, $map) {
    switch (TRUE) {
      case ($map->type == "boolean"):
        return preg_match("/^[Y,1]$/i", $text);
      case ( (is_numeric($text)) && (isset($map->decimal)) ):
        return number_format($text, $map->decimal, ".", "");
      case ( (is_numeric($text)) && (isset($map->fixed)) ):
        return substr("0000000000" . intval($text), ($map->fixed * -1));
      case ( (isset($map->decimal)) || (isset($map->fixed)) ):
        return "";
      default:
        return $text;
    }
}
?>