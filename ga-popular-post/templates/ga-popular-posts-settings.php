<?php
/**
 * Created by PhpStorm.
 * User: KHANH
 * Date: 8/31/2017
 * Time: 3:41 PM
 */
?>
<div class="wrap">
    <h2><?php _e( "GA Popular Posts Settings" ); ?></h2>
    <?php
    if( isset( $_POST['save-gapp-setting'])){
        update_option('gapp_settings', $_POST );
        if(!empty($_FILES["key_file"]["tmp_name"])) {
            $urls = wp_handle_upload($_FILES["key_file"], array('test_form' => FALSE));
            $temp = $urls["file"];
            update_option('gapp_key_file', $temp);
        }
        do_action( 'update_gapp_setting_fields');
    }
    $pp_data = get_option( 'gapp_settings');
    $data = array(
        'ga_view_id' => !(empty( $pp_data['ga_view_id'] ) ) ? $pp_data['ga_view_id'] : '',
    );
    $data = apply_filters( 'gapp_field_settings', $data );
    ?>
    <div class="gapp_settings">
        <form action="" name="" class="" method="post" enctype="multipart/form-data">
            <?php
            do_action( 'before_gapp_settings');
            ?>
            <h2>Google Analytics</h2>
            <p>Enter the GA properties that corresponds to this site.</p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="ga_view_id">View ID</label></th>
                    <td>
                        <input class="regular-text" id="ga_view_id" name="ga_view_id" value="<?php echo $data['ga_view_id']; ?>" placeholder="" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="key_file">Credentials Key File</label></th>
                    <td>
                        <?php if(get_option('gapp_key_file')) {
                            echo basename(get_option('gapp_key_file')).'<br><br>';
                            echo '<span>Choose another key file:</span>';
                        }
                        ?>
                        <input type="file" name="key_file">
                    </td>
                </tr>
                </tbody>
            </table>
            <?php do_action( 'after_gapp_settings'); ?>
            <?php submit_button( 'Save Change', 'primary','save-gapp-setting'); ?>
        </form>
    </div>
    <h2 style="font-size: 23px;">Documents</h2>
    <div class="gappDocs">
        <style type="text/css">.gappDocs img {width: auto;max-width: 100%}</style>
    <p>You can refer the Google Analytics tutorial at <a href="https://developers.google.com/analytics/devguides/reporting/core/v3/quickstart/service-php" target="_blank">this link</a></p>
    <h3>Step 1: Enable the Analytics API</h3>
    <p>To get started using Google Analytics API, you need to first
        <a href="https://console.developers.google.com/start/api?id=analytics&amp;credential=client_key" target="_blank">use
            the setup tool</a>, which guides you through creating a project in the
        Google API Console, enabling the API, and creating credentials.</p>

    <h4>Create a client ID</h4>
    <ol>
        <li>Open the <a href="https://console.developers.google.com/permissions/serviceaccounts" target="_blank"><b>Service accounts</b> page</a>. If prompted,
            select a project.</li>
        <li>Click <b>Create service account</b>.</li>
        <li>

            In the <b>Create service account</b> window, type a name for the service
            account, and select <b>Furnish a new private key</b>. If you want to
            <a href="https://developers.google.com/identity/protocols/OAuth2ServiceAccount#delegatingauthority" target="_blank">grant
                G Suite domain-wide authority</a> to the service account, also select
            <b>Enable G Suite Domain-wide Delegation</b>.

            Then click <b>Create</b>.</li>
    </ol>
    <p>Your new public/private key pair is generated and downloaded to your machine;
        it serves as the only copy of this key. You are responsible for storing it
        securely.</p>
    <aside class="note">

        When prompted for the <i>Key type</i> select <b>JSON</b>, and save the
        generated key as
        <code>service-account-credentials.json</code>; you will need it later in the tutorial.

    </aside>
    <h4 id="add-user">Add service account to Google Analytics account</h4>
    <p>The newly created service account will have an email address,
        <code>&lt;projectId&gt;-&lt;uniqueId&gt;@developer.gserviceaccount.com</code>;
        Use this email address to
        <a href="https://support.google.com/analytics/answer/1009702" target="_blank">add
            a user</a> to the Google analytics account you want to access via the API.
        For this tutorial only
        <a href="https://support.google.com/analytics/answer/2884495" target="_blank">Read
            &amp; Analyze</a> permissions are needed.
    </p>
    <h3 id="install">Step 2: Set up GA properties from GA Popular Posts plugin</h3>
        <h4>View ID</h4>
    <p>Find the View ID that corresponds to the Google Analytics table tracking your site, and save it in the Property View ID field. You can find the View ID in your Google Analytics account > Administration > View Settings:</p>
        <p><img src="<?php echo __GAPP_URL__;?>/assets/img/2.png" alt=""></p>
        <p><img src="<?php echo __GAPP_URL__;?>/assets/img/analytics-view-settings.png" alt=""></p>
        <h4>Credential Key File</h4>
        <p>Add the json key file from step 1 to this field.</p>
        <p>Click the Save Changes button and you are done setting up the GA Popular Posts plugin.</p>
    </div>
</div><!-- END .wrap -->