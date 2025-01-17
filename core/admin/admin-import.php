<?php
/**
 * Displays the import games page on backend
 *
 * @author Daniel Bakovic <contact@myarcadeplugin.com>
 */

// No direct access
if( !defined( 'ABSPATH' ) ) {
  die();
}

/**
 * Import games page
 *
 * @version 5.14.2
 * @access  public
 * @return  void
 */
function myarcade_import_games() {
  global $wpdb;

  myarcade_header();

  $general= get_option( 'myarcade_general' );

  // Crete an empty game class
  $game = new stdClass();

  if ( isset($_POST['impcostgame']) && ($_POST['impcostgame'] == 'import') ) {
    if ( $_POST['importtype'] == 'embed' || $_POST['importtype'] == 'iframe' ) {
      $decoded = urldecode( $_POST['importgame'] );
      $converted = str_replace( array("\r\n", "\r", "\n"), " ", $decoded);
      $game->swf_url = esc_sql( $converted );
    }
    else {
      $game->swf_url = $_POST['importgame'];
    }

    $game->width = !empty($_POST['gamewidth']) ? $_POST['gamewidth'] : '';
    $game->height = !empty($_POST['gameheight']) ? $_POST['gameheight'] : '';

    if ( ($_POST['importtype'] == 'ibparcade') OR ($_POST['importtype'] == 'phpbb') ) {
      $game->slug = $_POST['slug'];
    }
    else {
      $game->slug           = preg_replace("/[^a-zA-Z0-9 ]/", "", strtolower($_POST['gamename']));
      $game->slug           = str_replace(" ", "-", $game->slug);
    }

    $game->name           = $_POST['gamename'];
    $game->type           = $_POST['importtype'];
    $game->uuid           = md5($game->name.'import');
    $game->game_tag       = ( !empty($_POST['importgametag'])) ? $_POST['importgametag'] : crc32($game->uuid);
    $game->thumbnail_url  = $_POST['importthumb'];
    $game->description    = $_POST['gamedescr'];
    $game->instructions   = $_POST['gameinstr'];
    $game->tags           = esc_sql( $_POST['gametags'] );
    $game->categs         = ( isset($_POST['gamecategs']) ) ? implode(",", $_POST['gamecategs']) : 'Other';
    $game->created        = gmdate( 'Y-m-d H:i:s', ( time() + (get_option( 'gmt_offset' ) * 3600 ) ) );
    $game->leaderboard_enabled = filter_input( INPUT_POST, 'lbenabled' );

    if ( ! empty( $_POST['highscoretype'] ) && 'low' == $_POST['highscoretype'] ) {
      $game->highscore_type = 'ASC';
    }
    else {
      $game->highscore_type = 'DESC';
    }

    $game->status         = 'new';
    $game->screen1_url    = $_POST['importscreen1'];
    $game->screen2_url    = $_POST['importscreen2'];
    $game->screen3_url    = $_POST['importscreen3'];
    $game->screen4_url    = $_POST['importscreen4'];
    $game->video_url      = isset($_POST['video_url']) ? $_POST['video_url'] : '';
    $game->score_bridge   = isset($_POST['score_bridge']) ? $_POST['score_bridge'] : '';

    // Add game to table
    myarcade_insert_game($game);

    // Add the game as blog post
    if ($_POST['publishstatus'] != 'add') {
      $gameID = $wpdb->get_var("SELECT id FROM " . $wpdb->prefix . 'myarcadegames' . " WHERE uuid = '$game->uuid'");

      if ( !empty($gameID) ) {
        myarcade_add_games_to_blog( array('game_id' => $gameID, 'post_status' => $_POST['publishstatus'], 'echo' => false) );

        echo '<div class="mabp_info mabp_680"><p>'.sprintf(__("Import of '%s' was succsessful.", 'myarcadeplugin'), $game->name).'</p></div>';
      }
      else  {
        echo '<div class="mabp_error mabp_680"><p>'.__("Can't import that game...", 'myarcadeplugin').'</p></div>';
      }
    }
    else {
      echo '<div class="mabp_info mabp_680"><p>'. sprintf(__("Game added successfully: %s", 'myarcadeplugin'), $game->name).'</p></div>';
    }
  }

  // Generate the category array
  if ( $general['post_type'] != 'post' && post_type_exists( $general['post_type'] )
    && !empty( $general['custom_category']) && taxonomy_exists($general['custom_category']) ) {

    $taxonomy = $general['custom_category'];
  }
  else {
    $taxonomy = 'category';
  }

  $categories = get_terms( $taxonomy, array('hide_empty' => false) );
  $selected_method = filter_input( INPUT_POST, 'importmethod', FILTER_SANITIZE_STRING, array( "options" => array( "default" => 'importswfdcr') ) );
  ?>

  <?php require_once( MYARCADE_JS_DIR . '/admin-import-js.php'); ?>
  <div id="myabp_import">
    <h2><?php _e("Import Individual Games", 'myarcadeplugin'); ?></h2>

    <div class="container">
      <div class="block">
        <table class="optiontable" width="100%">
          <tr>
            <td><h3><?php _e("Import Method", 'myarcadeplugin'); ?></h3></td>
          </tr>
          <tr>
            <td>
              <select size="1" name="importmethod" id="importmethod">
                <option value="importswfdcr" <?php selected( "importswfdcr", $selected_method ); ?>><?php _e("Upload / Grab SWF game", 'myarcadeplugin'); ?>&nbsp;</option>
                <option value="importembedif" <?php selected( "importembedif", $selected_method ); ?>><?php _e("Import Embed / Iframe game", 'myarcadeplugin'); ?></option>
              </select>
              <br />
              <i><?php _e("Choose a desired import method.", 'myarcadeplugin'); ?></i>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <?php myarcade_get_max_post_size_message(); ?>

    <?php require_once( 'import_form.php' ); ?>
  </div><?php // end #myabp_import ?>
  <div class="clear"></div>
  <?php
  myarcade_footer();
}
?>