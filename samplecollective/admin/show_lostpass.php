<?php
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
 ******************************************************************************/

use RobotessNet\StringUtils;

require_once 'config.php';

require_once('mod_errorlogs.php');
require_once('mod_owned.php');
require_once('mod_members.php');
require_once('mod_settings.php');
require_once('mod_emails.php');

$install_path = get_setting('installation_path');
require_once($install_path . 'Mail.php');

if (!isset($listing)) {
    echo '!! You haven\'t set $listing variable in config.php. Please set it first - the instruction is at the end of the file.<br/>';

    return;
}

// get listing info
$info = get_listing_info($listing);

// initialize variables
$show_form = true;
$errorstyle = ' style="font-weight: bold; display: block;" ' .
    'class="show_lostpass_error"';

// process forms
if (isset($_POST['enth_email']) && $_POST['enth_email'] != '') {
    // do some spam/bot checking first
    $goahead = false;
    // 1. check that user is submitting from browser
    // 2. check the POST was indeed used
    if (isset($_SERVER['HTTP_USER_AGENT']) &&
        $_SERVER['REQUEST_METHOD'] === 'POST') {
        $goahead = true;
    }

    if (!$goahead) {
        echo "<p$errorstyle>ERROR: Attempted circumventing of the form detected.</p>";
        return;
    }

    $cleanNormalizedEmail = StringUtils::instance()->cleanNormalize($_POST['enth_email']);

    $email = '';
    if (!StringUtils::instance()->isEmailValid($cleanNormalizedEmail) ||
        !ctype_graph($cleanNormalizedEmail)) {
        ?>
        <p style="font-weight: bold;" class="show_lostpass_bad_email">That
            email address is not valid. Please check your entered address and try
            again.</p>
        <?php
        return;
    }

    $email = $cleanNormalizedEmail;

    $member = get_member_info($listing, $email);
    // Check if $member is not null and is an array
    if (!is_array($member) || !isset($member['email']) || $member['email'] == '') {
        ?>
        <p style="font-weight: bold;" class="show_lostpass_no_such_member">There was an error in your request to reset your password. This may be because there is no member recorded in the <?= $info['listingtype'] ?> with that email address. Please check your spelling and try again.</p>
        <?php
    } else {
        $password = reset_member_password($listing, $member['email']);
        // send email
        $to = $member['email'];
        $subject = $info['title'] . ' ' . ucfirst($info['listingtype']) . ': Password Reset';
        $from = '"' . html_entity_decode($info['title'], ENT_QUOTES) . '" <' . $info['email'] . '>';
        $message = parse_email('lostpass', $listing, $member['email'], $password);
        $message = stripslashes($message);

        // use send_email function
        $mail_sent = send_email($to, $from, $subject, $message);

        if ($mail_sent) {
            ?>
            <p class="show_lostpass_processed_done">A password has been successfully generated for you and this has been sent to your email address. Please update this password as soon as possible for your own security.</p>
            <?php
        } else {
            ?>
            <p class="show_lostpass_processed_error">There was an error sending the generated password to you. Please email me instead and let me know of the problem.</p>
            <?php
        }
        $show_form = false;
    }
}

if ($show_form) {
    ?>
    <p class="show_lostpass_intro">If you have lost or forgotten your
        password, you can reset your password using this form. The new
        generated password will be sent to you, and we advise you to
        immediately change/update your password once you receive this.</p>

    <p class="show_lostpass_intro_instructions">Enter your email address on
        the field below to generate a password.</p>

    <form method="post" action="<?= $info['lostpasspage'] ?>"
          class="show_lostpass_form">

        <p class="show_lostpass_email">
   <span style="display: block;" class="show_lostpass_email_label">
   Email address: </span>
            <input type="email" name="enth_email" class="show_lostpass_email_field" required/>
            <input type="submit" value="Reset my password"
                   class="show_lostpass_submit_button"/>
        </p>

    </form>

    <!--// do not remove the credit link please-->
    <p class="show_lostpass_credits">
        <?php include ENTH_PATH . 'show_credits.php' ?>
    </p>
    <?php
}
