<?php


$iconname = "icon";
$filename = $_FILES["{$iconname}"]["name"];

if ((($_FILES["{$iconname}"]["type"] == "image/gif")
    || ($_FILES["{$iconname}"]["type"] == "image/jpeg")
    || ($_FILES["{$iconname}"]["type"] == "image/pjpeg")
    || ($_FILES["{$iconname}"]["type"] == "image/png"))
  )
{
    if ($_FILES["{$iconname}"]["error"] > 0) {
        echo '{"code":"0","msg":"'. $_FILES["{$iconname}"]["error"] .'","flag":"[HTTP_SSDUTXYYX]"}';
    } else {
            if (file_exists("icon/{$filename}")) {    //删除旧头像
                unlink("icon/{$filename}");
            } 
            move_uploaded_file($_FILES["{$iconname}"]["tmp_name"],
                "icon/{$filename}");
            echo '{"code":"1","msg":"Upload Success!","flag":"[HTTP_SSDUTXYYX]"}';
        }
    } else {
      echo '{"code":"0","msg":"Invalid file!","flag":"[HTTP_SSDUTXYYX]"}';
  }

?>