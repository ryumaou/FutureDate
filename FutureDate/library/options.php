<?php

// Security recommendation from wp.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// create custom plugin settings menu
add_action('admin_menu', 'futuredate_create_menu');

//call custom post function to associate all posts with futuredate
add_action('admin_post_update_futuredates', 'futuredate_admin_futuredate_all_posts');

function futuredate_create_menu()
{
    //create new options menu
    add_options_page('Future Date Setup','Future Date','manage_options','futuredate','futuredate_settings_page');

    //call register settings function
    add_action( 'admin_init', 'register_futuredate_settings' );
}

function register_futuredate_settings()
{
    //register our settings
    register_setting( 'futuredate-settings', 'futuredate_prefix', 'futuredate_validate_string' );
    register_setting( 'futuredate-settings', 'futuredate_adjustment', 'futuredate_validate_string' );
    register_setting( 'futuredate-settings', 'futuredate_style', 'futuredate_validate_style' );
    register_setting( 'futuredate-settings', 'futuredate_fortyk_check', 'futuredate_validate_fortyk_check' );
    register_setting( 'futuredate-settings', 'futuredate_override_get_date', 'futuredate_validate_checkbox' );
    register_setting( 'futuredate-settings', 'futuredate_override_time', 'futuredate_validate_checkbox' );
    register_setting('futuredate-settings', 'futuredate_override_even_when_explicit_format', 'futuredate_validate_checkbox');
}

function futuredate_validate_string( $input )
{
    /**
     * validate and escape the string before saving
     * 
     * @var string $input the input to validate
     */
    // TODO: some extra validation maybe. string len, etc    
    return wp_filter_nohtml_kses( $input );
}

function futuredate_validate_checkbox( $input )
{
    /**
     * validate the checkboxes
     *
     * @var int $input the input to validate
     */
     return ( $input == 1 ? 1 : 0 );
}

function futuredate_validate_style( $input )
{
    /**
     * validate the style. 
     *
     * @var string $input the input to validate
     */
    return ( in_array($input, array('OrdinalDate', 'TC', '40KFuture')) ? $input : 'Classic');
}

function futuredate_validate_fortyk_check( $input )
{
    /**
     * validate the 40K check digit
     *
     * @var string $input the input to validate
     */
    return ( in_array($input, array('One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine')) ? $input : 'Zero');
}        

function futuredate_admin_futuredate_all_posts()
{
    /**
     * futuredate all posts. 
     * 
     */

    status_header(200);
    if ( !current_user_can( 'manage_options' ) )
    {
        wp_die( 'unauthorized' );
    }
    
    // here we loop through all posts and set the futuredate for them
    $r = futuredate_all_posts();

    wp_redirect(  admin_url( 'options-general.php?page=futuredate' ) );

}


function futuredate_settings_page()
{
?>
<div class="wrap">
  <h2>Future Date Options</h2>
  <form method="post" action="options.php">
    <?php settings_fields( 'futuredate-settings' ); ?>
    <?php do_settings_sections( 'futuredate-settings' ); ?>
     <table class="form-table">
        <tr valign="top">
          <th scope="row">Future Date Prefix</th>
          <td><input type="text" name="futuredate_prefix" value="<?php echo esc_attr( get_option('futuredate_prefix') ); ?>" /> This is the date or timestamp label.  It can be blank, if preferred.</td>
        </tr>
        <tr valign="top">
          <th scope="row">Future Date Adjustment</th>
          <td><input type="text" name="futuredate_adjustment" value="<?php echo esc_attr( get_option('futuredate_adjustment') ); ?>" /> This is the number of years into the future the date will be if you choose to use a future date format.<br> To use the current date, leave it set to 0 </td>
        </tr>
        <tr valign="top">
          <th scope="row">Style</th>
          <?php  $s = get_option('futuredate_style') ?>
          <td>
            <input name="futuredate_style" type="radio" value="TC" <?php checked( 'TC', get_option( 'futuredate_style' ) ); ?> /> Terran Computational Calendar Date <code><?php echo futuredate_now('TC')?> <br></code>
            <input name="futuredate_style" type="radio" value="OrdinalDate" <?php checked( 'OrdinalDate', get_option( 'futuredate_style' ) ); ?> /> Ordinal Date <code><?php echo futuredate_now('OrdinalDate')?> <br></code>
            <input name="futuredate_style" type="radio" value="40KFuture" <?php checked( '40KFuture', get_option( 'futuredate_style' ) ); ?> /> Warhammer 40K Date <code><?php echo futuredate_now('40KFuture')?> <br></code>
            <input name="futuredate_style" type="radio" value="Classic" <?php checked( 'Classic', get_option( 'futuredate_style' ) ); ?> /> Classic Stardate (trekguide) <code><?php echo futuredate_now('Classic')?> <br></code>            
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Warhammer 40K Check Number</th>
          <?php  $s = get_option('futuredate_fortyk_check') ?>
          <td>
            <input name="futuredate_fortyk_check" type="radio" value="Zero" <?php checked( 'Zero', get_option( 'futuredate_fortyk_check' ) ); ?> /> 0 - Earth Standard Date <br>
            <input name="futuredate_fortyk_check" type="radio" value="One" <?php checked( 'One', get_option( 'futuredate_fortyk_check' ) ); ?> /> 1 - Sol System Standard Date <br>
            <input name="futuredate_fortyk_check" type="radio" value="Two" <?php checked( 'Two', get_option( 'futuredate_fortyk_check' ) ); ?> /> 2 - Direct <br>
            <input name="futuredate_fortyk_check" type="radio" value="Three" <?php checked( 'Three', get_option( 'futuredate_fortyk_check' ) ); ?> /> 3 - Indirect <br>
            <input name="futuredate_fortyk_check" type="radio" value="Four" <?php checked( 'Four', get_option( 'futuredate_fortyk_check' ) ); ?> /> 4 - Corroborated <br>            
            <input name="futuredate_fortyk_check" type="radio" value="Five" <?php checked( 'Five', get_option( 'futuredate_fortyk_check' ) ); ?> /> 5 - Sub-Corroborated <br>
            <input name="futuredate_fortyk_check" type="radio" value="Six" <?php checked( 'Six', get_option( 'futuredate_fortyk_check' ) ); ?> /> 6 - Non-Referenced, accurate within circa 1 year <br>
            <input name="futuredate_fortyk_check" type="radio" value="Seven" <?php checked( 'Seven', get_option( 'futuredate_fortyk_check' ) ); ?> /> 7 - Non-Referenced, accurate within circa 10 years <br>
            <input name="futuredate_fortyk_check" type="radio" value="Eight" <?php checked( 'Eight', get_option( 'futuredate_fortyk_check' ) ); ?> /> 8 - Non-Referenced, accurate within circa 11 or more years <br>
            <input name="futuredate_fortyk_check" type="radio" value="Nine" <?php checked( 'Nine', get_option( 'futuredate_fortyk_check' ) ); ?> /> 9 - Approximation <br>            
          </td>
        </tr>
        <tr>
          <td colspan=2>
             Themes may use different functions to get the date for a given post etc. There are two functions used quite often though: <code>the_date</code> (which cannot be filtered) and <code>get_the_date</code>.
              In some themes like the default one, <code>get_the_date</code> is used for the date tag with the 'U' format, and to keep that format, the default behavior is to NOT override get_the_date if it is called with explicit date formatting string. When overridden, the functions call the  <code> get_the_futuredate </code> or <code>the_futuredate</code> functions, which can also be used directly in the templates.
          </td>
        </tr>
          <tr valign="top">
          <th scope="row">Override get_the_date</th>
          <td>
            <input name="futuredate_override_get_date" type="checkbox" value="1" <?php checked( '1', get_option( 'futuredate_override_get_date' ) ); ?> />
          </td>
        </tr>
          <tr valign="top">
            <th scope="row">Override the_time</th>
            <td>
              <input name="futuredate_override_time" type="checkbox" value="1" <?php checked( '1', get_option( 'futuredate_override_time' ) ); ?> />
            </td>
          </tr>
         <tr valign="top">
             <th scope="row">Override even when a format is passed to the get_the_date functions</th>
             <td>
                 <input name="futuredate_override_even_when_explicit_format" type="checkbox" value="1" <?php checked( '1', get_option( 'futuredate_override_even_when_explicit_format' ) ); ?> />
             </td>
         </tr>
     </table>
    <?php submit_button( 'Time is a construct'); ?>
  </form>
  <form method="post" action="admin-post.php">
      <table class="form-table">
          <tr valign="top">
          <th scope="row">Associate futuredate with all posts</th>
        </tr>
        <tr>
          <td>
            Press the button down here to calculate futuredate for all posts. Needed for having futuredate in the url. Needs pushing when style is changed etc.
          </td>
        </tr>
        <tr>
          <td>
             <input type="hidden" name="action" value="update_futuredates" />
             <?php submit_button( 'Set futuredate for all posts', 'secondary'); ?>
           </td>
         </tr>
       </table>
   </form>
</div>
<?php } ?>
