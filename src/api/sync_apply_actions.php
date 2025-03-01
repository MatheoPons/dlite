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
     * along with this program.  If not, see <https://www.gnu.org/licenses/>.
     */

    include 'config.php';
    include 'rest_headers.php';
    include 'init_session.php';

    $dir = $SYNC_DATA_DIR.'/'.$_GET['user'];
	if (!is_dir($dir)) {
	  mkdir($dir);
	}

    $key = $_GET['key'];
    $file = $dir.'/'.$key.'.json';

    $syncResult = [];
    if (file_exists($file)) {
        $serverValue = json_decode(file_get_contents($file), true);
        if (!array_key_exists('data', $serverValue) || !array_key_exists('version', $serverValue)) {
            $syncResult['error'] = "wrong server file";
            exit(json_encode($syncResult));
        }
        $data = json_decode($serverValue['data']);
        $version = $serverValue['version'];
    } else {
        $serverValue = [];
        $version = 1;
        $data = [];
    }

	$body = file_get_contents("php://input");
    $actions = json_decode($body, true);

    function array_push_before ($src, $in, $pos){
        return array_merge(array_slice($src, 0, $pos), $in, array_slice($src, $pos));
    }

    foreach ($actions as $action) {
        $item = $action['item'];
        switch ($action['type']) {
            case 'ADD':
                array_push($data, $item);
                break;
            case 'REPLACE':
                for ($i = 0; $i < count($data); $i++) {
                    if ($data[$i]['id'] == $item['id']) {
                        $data[$i] = $item;
                        break;
                    }
                }
                break;
            case 'REMOVE':
                for ($i = 0; $i < count($data); $i++) {
                    if ($data[$i]['id'] == $item['id']) {
                        array_splice($data, $i, 1);
                        break;
                    }
                }
                break;
            case 'INSERT_AT':
                $data = array_push_before($data, $item, $action['index']);
                break;
            case 'REPLACE_AT':
                $data[$action['index']] = $item;
                break;
            case 'REMOVE_AT':
                array_splice($data, $action['index'], 1);
                break;
            case 'CONCAT_ARRAY':
                $data = array_merge($data, $item);
                break;
            case 'INSERT_ARRAY_AT':
                $data = array_push_before($data, $item, $action['index']);
                break;
            case 'MOVE_FROM_TO':
                $fromItem = $data[$action['from']];
                $data = array_push_before($data, $fromItem, $action['to']);
                array_splice($data, i, $action['from']);
                break;
            case 'CLEAR':
                $data = [];
                break;
            default:
                $syncResult['error'] = "wrong action type: ".$action['type'];
                exit(json_encode($syncResult));
        }
    }

    $serverValue['data'] = json_encode($data);
    $serverValue['version'] = $serverVersion + 1;
    $writeResult = file_put_contents($file, json_encode($serverValue));
    if (!$writeResult) {
        $syncResult['written'] = false;
        $syncResult['error'] = "writing file content failed";
        $syncResult['file'] = $file;
    } else {
        $syncResult['written'] = $writeResult;
        $syncResult['version'] = $serverValue['version'];
        $syncResult['data'] = $data;
    }

    echo json_encode($syncResult);


?> 

