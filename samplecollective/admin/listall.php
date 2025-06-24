<?php
/*****************************************************************************
 Enthusiast: Listing Collective Management System
 Copyright (c) by Angela Sabas
 http://scripts.indisguise.org/

 This script is made available for free download, use, and modification as
 long as this note remains intact and a link back to
 http://scripts.indisguise.org/ is given. It is hoped that the script
 will be useful, but does not guarantee that it will solve any problem or is
 free from errors of any kind. Users of this script are forbidden to sell or
 distribute the script in whole or in part without written and explicit
 permission from me, and users agree to hold me blameless from any
 liability directly or indirectly arising from the use of this script.

 For more information please view the readme.txt file.
******************************************************************************/
session_start();
require_once( 'logincheck.inc.php' );
if( !isset( $logged_in ) || !$logged_in ) {
   $_SESSION['message'] = 'You are not logged in. Please log in to continue.';
   $next = '';
   if( isset( $_SERVER['REQUEST_URI'] ) )
      $next = $_SERVER['REQUEST_URI'];
   else if( isset( $_SERVER['PATH_INFO'] ) )
      $next = $_SERVER['PATH_INFO'];
   $_SESSION['next'] = $next;
   header( 'location: index.php' );
   die( 'Redirecting you...' );
   }
require_once( 'header.php' );
require_once( 'config.php' );
require_once( 'mod_categories.php' );
require_once( 'mod_owned.php' );
require_once( 'mod_settings.php' );
require_once( 'mod_members.php' );

$show_default = true;
?>
<p class="title">
List all members
</p>
<?php
$action = '';
if( isset( $_REQUEST["action"] ) )
   $action = $_REQUEST['action'];


/*______________________________________________________________________LIST_*/
if( $action == 'list' ) {
   $show_default = false;
   $info = get_listing_info( $_REQUEST['id'] );
?>
      <p>
      Listed below are ALL members of <i><?= $info['title']
      ?>: <?= $info['subject'] ?> <?= $info['listingtype'] ?></i>. If you wish
      to send an email to all members,
      <a href="emails.php?action=members&id=<?= $info['listingid'] ?>">click
      here</a>.
      </p>

<?php
      $members = get_members( $info['listingid'] );
?>
      <p class="center"><table>

      <tr><td><b>Email</b></td>
<?php
      if( $info['country'] )
         echo '<td><b>Country</b></td>';
?>
      <td><b>Name</b></td>
      <td><b>URL</b></td>
<?php
      if( $info['additional'] != '' )
         echo '<td><b>Additional</b></td>';
?>
      </tr>
<?php
      foreach( $members as $member ) {
?>
         <tr><td>
         <?= $member['email'] ?>
         </td><td>
<?php
         if( $info['country'] == 1 )
            echo $member['country'] . '</td><td>';
?>
         <?= $member['name'] ?>
         </td><td>
         <a href="<?= $member['url'] ?>" target="<?= $info['linktarget'] 
            ?>"><?= $member['url']?></a>
         </td>
<?php
         if( $info['additional'] != '' ) {
            echo '<td>';
            foreach( explode( ',', $info['additional'] ) as $field )
               if( $field != '' )
                  echo '<i>' . ucfirst( $field ) . '</i>: ' . $member[$field] .
                     '<br />';
            echo '</td>';
            }
?>
         </tr>
<?php
         }
?>
      </table></p>
<?php
   }


if( $show_default ) {
?>
   <p>
   Via this section, you can list ALL the members in the fanlisting you select
   below. Please note that this page might take time loading when you're
   viewing a HUGE members list -- there will be no paging, all their
   info will be placed in a table on this page.
   </p>

   <p>
   Please note that this is only a LIST.
   </p>

   <form action="listall.php" method="post">
   <input type="hidden" name="action" value="list" />

   <p class="center">
   <select name="id">
<?php
   $owned = get_owned();
   foreach( $owned as $id ) {
      $info = get_listing_info( $id );
      echo '<option value="' . $id . '">' . $info['subject'] . ' ' .
         $info['listingtype'] . '</option>';
      }
?>
   </select>

   <input type="submit" value="List all members" />

   </p></form>

<?php
   }
require_once( 'footer.php' );
?>