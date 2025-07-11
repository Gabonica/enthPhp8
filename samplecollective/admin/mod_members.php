<?php

use RobotessNet\StringUtils;

/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Angela Sabas http://scripts.indisguise.org/
 * Copyright (c) 2018 by Lysianthus (contributor) <she@lysianth.us>
 * Copyright (c) 2019 by Ekaterina (contributor) http://scripts.robotess.net
 *
 * Enthusiast is a tool for (fan)listing collective owners to easily
 * maintain their listing collectives and listings under that collective.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * For more information please view the readme.txt file.
 *****************************************************************************
 */

/*___________________________________________________________________________*/
function parse_email($type, $listing, $email, $password = '')
{
    require('config.php');

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    // get owner name
    $query = "SELECT `value` FROM `$db_settings` WHERE `setting` = " .
        '"owner_name"';
    $result = $db_link->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $owner_name = $row['value'];

    // connect to listing database
    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die('Cannot connect: ' . $e->getMessage());
    }

    // get member info
    $query = "SELECT * FROM `$table` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(:email))";
    $result = $db_link_list->prepare($query);
    $result->bindParam(':email', $email);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $fan = $result->fetch();

    // get template
    $template = '';
    if ($type === 'signup') {
        $template = $info['emailsignup'];
    } else if ($type === 'approved') {
        $template = $info['emailapproved'];
    } else if ($type === 'update') {
        $template = $info['emailupdate'];
    } else if ($type === 'lostpass') {
        $template = $info['emaillostpass'];
    }

    // search and replace special variables
    $template = str_replace(['$$owner_name$$', '$$fanlisting_title$$', '$$fanlisting_subject$$', '$$fanlisting_email$$', '$$fanlisting_url$$', '$$fanlisting_list$$', '$$fanlisting_update$$', '$$fanlisting_join$$', '$$fanlisting_lostpass$$', '$$listing_type$$', '$$fan_name$$', '$$fan_email$$'], [$owner_name, $info['title'], $info['subject'], $info['email'], $info['url'], $info['listpage'], $info['updatepage'], $info['joinpage'], $info['lostpasspage'], $info['listingtype'], $fan['name'], $fan['email']], $template);
    if ($info['country'] == 1) {
        $template = str_replace('$$fan_country$$', $fan['country'], $template);
    }
    $template = str_replace(['$$fan_url$$', '$$fan_password$$'], [$fan['url'], $password], $template);

    // Provide a default value if $info['additional'] is null
    $additionalFields = $info['additional'] ?? ''; // Using the null coalescing operator to default to an empty string

    $fields = explode(',', $additionalFields);
    foreach ($fields as $field) {
        if ($field == '') {
            continue;
        }
        $template = str_replace('$$fan_' . $field . '$$', $fan[$field], $template);
    }

    $db_link_list = null;
    $db_link = null;
    return $template;
    }


/*___________________________________________________________________________*/
function get_members($listing, $status = 'all', $sort = [], $start = 'none', $bydate = 'no', $perpage = null)
{
    require('config.php');

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];
    $perpage = $perpage ?? $info['perpage'];

    // create limit query
    $limit_query = '';
    if ($start !== 'none' && $start !== 'all' && ctype_digit($start)) {
        $limit_query = " LIMIT $start, $perpage";
    }

    // create sorting criteria query
    $sorter_query = '';
    if (count($sort) > 0) {
        // we're sorting, possible more than one field
        foreach ($sort as $col => $value) {
            if ($status === 'all') {
                $sorter_query .= ' WHERE';
            } else {
                $sorter_query .= ' AND';
            }
            $comparison = (substr_count($value, '%') > 0) ? 'LIKE' : '=';
            $col = trim($col);
            $sorter_query .= " `$col` $comparison '$value'";
        }
    }

    // connect to actual db
    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // piece together the query
    $query = "SELECT * FROM `$table`";

    if ($status === 'pending') {
        $query .= ' WHERE `pending` = 1';
    } else if ($status === 'approved') {
        $query .= ' WHERE `pending` = 0';
    }

    $query .= $sorter_query;

    if ($bydate === 'bydate') {
        $query .= ' ORDER BY `added` DESC';
    } else {
        $query .= ' ORDER BY `name` ASC';
    }
    $query .= $limit_query;

    // get results
    $result = $db_link_list->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $members = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $members[] = $row;
    }

    $db_link_list = null;
    $db_link = null;
    return $members;
}


/*___________________________________________________________________________*/
function delete_member($listingId, $email)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $listingId, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    // connect to actual database
    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // delete fan
    $query = "DELETE FROM `$table` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(:email))";
    $result = $db_link_list->prepare($query);
    $result->bindParam(':email', $email);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $db_link_list = null;
    $db_link = null;
    return $result;
}


/*___________________________________________________________________________*/
function approve_member($id, $email)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $id, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // approve member
    $query = "UPDATE `$table` SET `pending` = 0, `added` = CURDATE() WHERE " .
        'LOWER(TRIM(`email`)) = LOWER(TRIM(:email))';
    $result = $db_link_list->prepare($query);
    $result->bindParam(':email', $email);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $db_link_list = null;
    $db_link = null;
    return $result;
}

/*___________________________________________________________________________*/
function get_member_info($listing, $email)
{
    require('config.php');

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }


    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get member info
    $query = "SELECT * FROM `$table` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(:email))";
    $result = $db_link_list->prepare($query);
    $result->bindParam(':email', $email);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    if ($row === false) {
        return null;
    }

    $db_link_list = null;
    $db_link = null;
    return $row;
}


/*___________________________________________________________________________*/
function edit_member_info($id, $email, $fields, $hold = 'no')
{
    require 'config.php';
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
$result = $db_link->prepare($query);
$result->bindParam(':id', $id, PDO::PARAM_INT);
$result->execute();
if (!$result) {
    log_error(__FILE__ . ':' . __LINE__,
        'Error executing query: <i>' . $result->errorInfo()[2] .
        '</i>; Query is: <code>' . $query . '</code>');
    die(STANDARD_ERROR);
}
$result->setFetchMode(PDO::FETCH_ASSOC);
$info = $result->fetch();
$table = $info['dbtable'];
$dbserver = $info['dbserver'];
$dbdatabase = $info['dbdatabase'];
$dbuser = $info['dbuser'];
$dbpassword = $info['dbpassword'];

// Provide a default value if $info['additional'] is null
$additional = $info['additional'] ?? ''; // Using the null coalescing operator to default to an empty string

try {
    $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
    $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(DATABASE_CONNECT_ERROR . $e->getMessage());
}

foreach ($fields as $field => $value) {
    $query = '';
    switch ($field) {

        case 'id' :
            break;
        case 'showurl' :
        case 'showemail' :
            $query = "UPDATE `$table` SET `$field` = ";
            if ($value === 'show') {
                $value = 1;
            } else if ($value === 'hide') {
                $value = '0';
            } else {
                continue 2;
            }
            $query .= "'$value' WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
            break;

        case 'name' :
        case 'country' :
            $col = $field;
            $value = StringUtils::instance()->clean($value);
            $query = "UPDATE `$table` SET `$col` = '$value' " .
                "WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
            break;

        case 'email_new' :
        case 'url' :
            $col = $field;
            if ($field === 'email_new') {
                $col = 'email';
            }
            $value = StringUtils::instance()->cleanNormalize($value);
            $query = "UPDATE `$table` SET `$col` = '$value' " .
                "WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
            if ($field === 'email_new') {
                $email = $value;
            }
            break;

        case 'password' :
            if ($value != '') {
                $query = "UPDATE `$table` SET `password` = " .
                    "MD5( '$value' ) WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
            }
            break;

        case 'approved' :
            if ($value == '1') {
                $query = "UPDATE `$table` SET `pending` = 0 WHERE LOWER(TRIM(`email`)) = " .
                    "LOWER(TRIM('$email'))";
            }
            break;

        default :
            if (substr_count($additional, $field) > 0) {
                // update field
                $query = "UPDATE `$table` SET `$field` = '" .
                    $value . "' WHERE LOWER(TRIM(`email`)) = LOWER(TRIM('$email'))";
            }
            break;

    } // end switch

    if ($query) {
        $result = $db_link->query($query);
        if (!$result) {
            log_error(__FILE__ . ':' . __LINE__,
                'Error executing query: <i>' . $result->errorInfo()[2] .
                '</i>; Query is: <code>' . $query . '</code>');
            die(STANDARD_ERROR);
        }
    } // end if query
} // end foreach

    if ($hold !== 'no' && $info['holdupdate'] == 1) {
        // place on pending!
        $query = "UPDATE `$table` SET `pending` = 1 WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(:email))";
        $result = $db_link_list->prepare($query);
        $result->bindParam(':email', $email);
        $result->execute();
        if (!$result) {
            log_error(__FILE__ . ':' . __LINE__,
                'Error executing query: <i>' . $result->errorInfo()[2] .
                '</i>; Query is: <code>' . $query . '</code>');
            die(STANDARD_ERROR);
        }
    }

    // update added date
    $query = "UPDATE `$table` SET `added` = CURDATE() WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(:email))";
    $result = $db_link_list->prepare($query);
    $result->bindParam(':email', $email);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    return true;
}


/*___________________________________________________________________________*/
function search_members($search, $listing = '', $status = 'all',
                        $start = 'none', $perpage = 25)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();

    // connect to listing database
    try {
        $db_link_list = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // create query
    $query = 'SELECT * FROM `' . $info['dbtable'] . '` WHERE MATCH( ' .
        '`email`, `name`, ';
    if ($info['country'] != 0) {
        $query .= '`country`, ';
    }
    $query .= " `url` ) AGAINST( :search  IN BOOLEAN MODE )";

    if ($status === 'pending') {
        $query .= ' AND `pending` = 1';
    } else if ($status === 'approved') {
        $query .= ' AND `pending` = 0';
    }

    $query .= ' ORDER BY `name` DESC';

    if ($start !== 'none' && ctype_digit($start)) {
        $query .= " LIMIT $start, $perpage";
    }

    $result = $db_link->prepare($query);
    $result->bindParam(':search', $search, PDO::PARAM_STR);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $members = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $members[] = $row;
    }

    $db_link_list = null;
    $db_link = null;
    return $members;
}


/*___________________________________________________________________________*/
function get_member_sorter($listing, $level = 1, $top = [])
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo $db_user;
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }
    // get sortby
    $query = "SELECT `sort` FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $sortarray = explode(',', $row['sort']);
    foreach ($sortarray as $i => $s) {
        if (!$s) {
            continue;
        }
        $sortarray[$i] = trim($s);
    }
    // get sort level
    if (!isset($sortarray[$level])) {
        $level--;
    }
    $sort = $sortarray[$level];

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get sorters
    $query = "SELECT DISTINCT( `$sort` ) AS `sort` FROM `$table` WHERE " .
        '`pending` = 0';
    foreach ($top as $col => $val) { // filter off values
        $comparison = (substr_count($val, '%') > 0) ? 'LIKE' : '=';
        $query .= " AND `$col` $comparison '$val'";
    }
    $query .= ' ORDER BY `sort` ASC';
    $result = $db_link_list->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $sorters = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $sorters[] = $row['sort'];
    }

    $db_link_list = null;
    $db_link = null;
    return $sorters;
}


/*___________________________________________________________________________*/
function check_member_password($listing, $email, $attempt)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT * FROM `$table` WHERE LOWER(TRIM(`email`)) = LOWER(TRIM(:email)) AND " .
        '`password` = MD5( :attempt )';
    $result = $db_link_list->prepare($query);
    $result->bindParam(':email', $email);
    $result->bindParam(':attempt', $attempt);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $passwordvalid = false;
    if ($result->rowCount() > 0) {
        $passwordvalid = true;
    }

    return $passwordvalid;
}

/*___________________________________________________________________________*/
function reset_member_password($listing, $email)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user, $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :listing";
    $result = $db_link->prepare($query);
    $result->bindParam(':listing', $listing, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    try {
        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8', $dbuser, $dbpassword);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // create random password
    $password = '';
    $k = 0;
    while ($k <= 10) {
        $password .= chr(rand(97, 122));
        $k++;
    }

    // update record
    $query = "UPDATE `$table` SET `password` = MD5( :password ) WHERE " .
        'LOWER(TRIM(`email`)) = LOWER(TRIM(:email))';
    $result = $db_link_list->prepare($query);
    $result->bindParam(':password', $password);
    $result->bindParam(':email', $email);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $db_link_list = null;
    $db_link = null;
    return $password;
}
