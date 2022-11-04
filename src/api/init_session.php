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

    if(!isset($_SESSION)) {
        session_start();
    }

    $headers = apache_request_headers();

    if (isset($headers['Authorization']) && isset($KEYCLOAK) && $KEYCLOAK == true) {
        $curl = curl_init();
        $url = $KC_URL . '/realms/' . $KC_REALM . '/protocol/openid-connect/userinfo';
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: '. $headers['Authorization']
        ));
        // test with wrong token
//         curl_setopt($curl, CURLOPT_HTTPHEADER, array(
//             'Authorization: Bearer xxxxxxxx'
//         ));
        $result = curl_exec($curl);
        curl_close($curl);
        $result_object = json_decode($result, true);
        if (isset($result_object['error'])) {
            http_response_code(401);
            echo '{ "authorized":false, "userId":"'.$_SESSION['userId'].'", "user":"'.$_GET['user'].'", "sessionId": "'.session_id().'" }';
            die();
        }

    } else {
        if (!isset($_SESSION['userId']) || $_SESSION['userId'] != $_GET['user']) {
            http_response_code(401);
            echo '{ "authorized":false, "userId":"'.$_SESSION['userId'].'", "user":"'.$_GET['user'].'", "sessionId": "'.session_id().'" }';
            die();
        }
    }
?>