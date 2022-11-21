<?php

    /*
     * d.Lite - low-code platform for local-first Web/Mobile development
     * Copyright (C) 2022 CINCHEO
     *                    https://www.cincheo.com
     *                    renaud.pawlak@cincheo.com
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Affero General Public License as
     * published by the Free Software Foundation, either version 3 of the
     * License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU Affero General Public License for more details.
     *
     * You should have received a copy of the GNU Affero General Public License
     * along with this program. If not, see <https://www.gnu.org/licenses/>.
     */

    include 'config.php';
    include 'rest_headers.php';
    include 'init_session.php';

    if (!isset($_GET['user'])) {
        echo '{ "error": "user is not provided" }';
        die();
    }

    $unshare = true;

    if (isset($_GET['target_user'])) {
        $target_users = array($_GET['target_user']);
        $unshare = false;
    } else {
        if (!isset($_GET['target_users'])) {
            $target_users = array();
        } else {
            $target_users = $_GET['target_users'];
        }
    }

    if (!is_array($target_users)) {
        echo '{ "error": "target users must be an array" }';
        die();
    }

    $dir = $SYNC_DATA_DIR.'/'.$_GET['user'];
    $key = $_GET['key'];

    $file = $dir.'/'.$key.'.json';
    $result = false;
    $link_target = '../'.$_GET['user'].'/'.$key.'.json';
    $errors = 0;
    $shares = 0;
    $unshares = 0;
    $unshared_link_target = '../'.$_GET['user'].'/unshared';

    if ($unshare) {
        $syncDirHandle = opendir($SYNC_DATA_DIR);
        while($item = readdir($syncDirHandle)) {
            $userPath = $SYNC_DATA_DIR."/".$item;
            if(is_dir($userPath) && $item != '.' && $item != '..') {
                if ($item == $_GET['user']) {
                    continue;
                }
                if (in_array($item, $target_users)) {
                    continue;
                }
                $link = $userPath.'/'.$key.'-$-'.$_GET['user'].'.json';
                if (file_exists($link) && readlink($link) != $unshared_link_target) {
                    unlink($link);
                    symlink($unshared_link_target, $link);
                    $unshares++;
                }
            }
        }
    }

    foreach ($target_users as $target_user) {

        if ($_GET['user'] == $target_user) {
            continue;
        }

        $target_dir = $SYNC_DATA_DIR.'/'.$target_user;

        // if the user does not have a space yet, we create it so that s/he will access the data when joining the system
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (file_exists($file)) {
            $link = $target_dir.'/'.$key.'-$-'.$_GET['user'].'.json';
            if (readlink($link) != $link_target) {
                $result = unlink($link);
                $result = symlink($link_target, $link);
                $shares++;
            }
        } else {
            $errors++;
        }
    }

    if ($errors > 0) {
        echo '{ "link_target": "'.$link_target.'", "error": "'.$errors.' error(s) when sharing '.$key.'", "file": "'.$file.'" }';
    } else {
        echo '{ "link_target": "'.$link_target.'", "result": "'.$shares.' share(s) / '.$unshares.' unshare(s)", "file": "'.$file.'" }';
    }

?>

