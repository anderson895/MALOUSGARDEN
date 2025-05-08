<?php

if (!function_exists('get_ext')) {
    function get_ext($pdo, $fname)
    {
        $up_filename = $_FILES[$fname]["name"];
        $file_basename = substr($up_filename, 0, strripos($up_filename, '.')); // strip extension
        $file_ext = substr($up_filename, strripos($up_filename, '.')); // get extension
        return $file_ext;
    }
}

if (!function_exists('ext_check')) {
    function ext_check($pdo, $allowed_ext, $my_ext)
    {
        $arr1 = explode("|", $allowed_ext);
        $count_arr1 = count($arr1);

        for ($i = 0; $i < $count_arr1; $i++) {
            $arr1[$i] = '.' . $arr1[$i];
        }

        $stat = 0;
        for ($i = 0; $i < $count_arr1; $i++) {
            if ($my_ext === $arr1[$i]) {
                $stat = 1;
                break;
            }
        }

        return $stat === 1;
    }
}

if (!function_exists('get_ai_id')) {
    function get_ai_id($pdo, $tbl_name)
    {
        $statement = $pdo->prepare("SHOW TABLE STATUS LIKE :table");
        $statement->execute(['table' => $tbl_name]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return isset($result['Auto_increment']) ? $result['Auto_increment'] : null;
    }
}
