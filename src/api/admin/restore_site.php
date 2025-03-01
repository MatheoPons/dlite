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

    if (!isset($_GET['app'])) {
        echo '{ "error": "app is not provided" }';
        die();
    }

    include '../config.php';
    include '../rest_headers.php';
    include 'init_admin_session.php';
    include 'tools.php';

	//$body = file_get_contents("php://input");
    $rootPath = realpath('../..');
    $rootTmpDir = sys_get_temp_dir();

    if($_FILES["file"]["name"]) {
        $filename = $_FILES["file"]["name"];
        $source = $_FILES["file"]["tmp_name"];
        $type = $_FILES["file"]["type"];

        $name = explode(".", $filename);
        $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
        foreach($accepted_types as $mime_type) {
            if($mime_type == $type) {
                $okay = true;
                break;
            }
        }

        $continue = strtolower($name[1]) == 'zip' ? true : false;
        if(!$continue) {
            $error = true;
            $message = "The file you are trying to upload is not a .zip file. Please try again.";
        } else {

            // BACKUP
            if (!is_dir($rootTmpDir.'/site_backup')) {
                if (!mkdir($rootTmpDir.'/site_backup', 0777)) {
                    error_log('cannot create directory '.$rootTmpDir.'/site_backup');
                }
            }
            zipData($rootPath, $rootTmpDir.'/site_backup/'.$_GET['app'].'-site-'.date('Y-m-d-H-i-s-u').'.zip');


            if (!is_dir($rootTmpDir.'/site_upload')) {
                if (!mkdir($rootTmpDir.'/site_upload', 0777)) {
                    error_log('cannot create directory '.$rootTmpDir.'/site_upload');
                }
            }
            $targetZip = $rootTmpDir.'/site_upload/' . $filename;
            $targetTmpDir = $rootTmpDir.'/site_upload/' . $name[0];

            /* here it is really happening */

            if(move_uploaded_file($source, $targetZip)) {
                $zip = new ZipArchive();
                $x = $zip->open($targetZip);
                if ($x === true) {
                    if (!$zip->extractTo($targetTmpDir)) {
                        error_log('cannot extract zip '.$targetZip.' to '.$targetTmpDir);
                    }
                    $zip->close();

                    //unlink($targetZip);
                }
            } else {
                $error = true;
                $message = "There was a problem with the upload. Please try again. " . $source . ' - ' . $targetZip . ' - ' . $rootPath;
            }

            // CHECK UPLOADED BUNDLE VALIDITY (IF NO ERRORS)
            if (!isset($message)) {
                if (
                    !file_exists($targetTmpDir.'/index.html') ||
                    !is_dir($targetTmpDir.'/assets')
                ) {
                    $error = true;
                    $message = "Invalid zip content. Please check that your bundle content (index.html) is placed at the root of the zip file (and not in a directory).";
                } else {
                    foreach (scandir($targetTmpDir) as $file) {
                        if (preg_match('/([^-]*)-([^_]*)_(.*)\.min.js/', $file, $matches)) {
                            $appName = $matches[1];
                            $dliteVersion = $matches[2];
                            $bundleVersion = $matches[3];
                            $bundleName = $file;
                        }
                    }
                    if (!isset($bundleName)) {
                        $error = true;
                        $message = "Missing bundle file.";
                    } else {
                        foreach (scandir($rootPath) as $file) {
                            if (preg_match('/([^-]*)-([^_]*)_(.*)\.min.js/', $file, $matches)) {
                                if ($appName != $matches[1]) {
                                    $error = true;
                                    $message = "Wrong application upgrade (name is different).";
                                }
                                if ($file == $bundleName) {
                                    $error = true;
                                    $message = "Wrong application upgrade (same version).";
                                }
                            }
                        }
                    }
                }
            }

            // DO THE ACTUAL RESTORE (IF NO ERRORS)
            if (!isset($message)) {
                // backup current configuration
                if (!copy($rootPath.'/api/config.php', $rootTmpDir.'/site_backup/config.php')) {
                    error_log('cannot copy '.$rootPath.'/api/config.php'.' to '.$rootTmpDir.'/site_backup/config.php');
                }

                $zip = new ZipArchive();
                $x = $zip->open($targetZip);
                if ($x === true) {
                    if (!$zip->extractTo($rootPath)) {
                        error_log('cannot extract zip '.$targetZip.' to '.$rootPath);
                        $error = true;
                        $message = 'Cannot extract zip '.$targetZip.' to '.$rootPath;
                    }
                    $zip->close();
                    unlink($targetZip);
                }

                if (isset($error)) {
                    // restore current configuration
                    if (!copy($rootTmpDir.'/site_backup/config.php', $rootPath.'/api/config.php')) {
                        error_log('cannot copy '.$rootTmpDir.'/site_backup/config.php'.' to '.$rootPath.'/api/config.php');
                    }
                } else {
                    if (!file_exists($targetTmpDir.'/api/config.php')) {
                        $message = 'Successfully upgraded site (config unchanged)';
                    } else {
                        $message = 'Successfully override site (new config)';
                    }
                }
            }

        }
    }
    if (isset($error)) {
        error_log($message);
        echo '{ "error": true, "result": "'.$message.'"}';
    } else {
        echo '{ "result": "'.$message.'"}';
    }
?>