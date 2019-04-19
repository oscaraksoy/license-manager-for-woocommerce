<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php esc_html_e('Add new generator', 'lmfwc'); ?></h1>
<hr class="wp-header-end">

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')) ;?>">
    <input type="hidden" name="action" value="lmfwc_save_generator">
    <?php wp_nonce_field('lmfwc_save_generator'); ?>

    <table class="form-table">
        <tbody>
            <!-- NAME -->
            <tr scope="row">
                <th scope="row">
                    <label for="name"><?php esc_html_e('Name', 'lmfwc');?>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="name" id="name" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'lmfwc');?></b>
                        <span><?php esc_html_e('A short name to describe the generator.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- TIMES ACTIVATED MAX -->
            <tr scope="row">
                <th scope="row"><label><?php esc_html_e('Maximum activation count', 'lmfwc');?></label></th>
                <td>
                    <input name="times_activated_max" id="times_activated_max" class="regular-text" type="number">
                    <p class="description" id="tagline-description"><?php esc_html_e('Define how many times the license key can be marked as "activated" by using the REST API. Leave blank if you do not use the API.', 'lmfwc');?></p>
                </td>
            </tr>

            <!-- CHARSET -->
            <tr scope="row">
                <th scope="row">
                    <label for="charset"><?php esc_html_e('Character map', 'lmfwc');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="charset" id="charset" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'lmfwc');?></b>
                        <span><?php _e('i.e. for "12-AB-34-CD" the character map is <kbd>ABCD1234</kbd>.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- NUMBER OF CHUNKS -->
            <tr scope="row">
                <th scope="row">
                    <label for="chunks"><?php esc_html_e('Number of chunks', 'lmfwc');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="chunks" id="chunks" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'lmfwc');?></b>
                        <span><?php _e('i.e. for "12-AB-34-CD" the number of chunks is <kbd>4</kbd>.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- CHUNK LENGTH -->
            <tr scope="row">
                <th scope="row">
                    <label for="chunk_length"><?php esc_html_e('Chunk length', 'lmfwc');?></label>
                    <span class="text-danger">*</span></label>
                </th>
                <td>
                    <input name="chunk_length" id="chunk_length" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Required.', 'lmfwc');?></b>
                        <span><?php _e('i.e. for "12-AB-34-CD" the chunk length is <kbd>2</kbd>.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- SEPARATOR -->
            <tr scope="row">
                <th scope="row"><label for="separator"><?php esc_html_e('Separator', 'lmfwc');?></label></th>
                <td>
                    <input name="separator" id="separator" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'lmfwc');?></b>
                        <span><?php _e('i.e. for "12-AB-34-CD" the separator is <kbd>-</kbd>.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- PREFIX -->
            <tr scope="row">
                <th scope="row"><label for="prefix"><?php esc_html_e('Prefix', 'lmfwc');?></label></th>
                <td>
                    <input name="prefix" id="prefix" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'lmfwc');?></b>
                        <span><?php _e('Adds a word at the start (separator <b>not</b> included), i.e. <kbd><b>PRE-</b>12-AB-34-CD</kbd>.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- SUFFIX -->
            <tr scope="row">
                <th scope="row"><label for="suffix"><?php esc_html_e('Suffix', 'lmfwc');?></label></th>
                <td>
                    <input name="suffix" id="suffix" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'lmfwc');?></b>
                        <span><?php _e('Adds a word at the end (separator <b>not</b> included), i.e. <kbd>12-AB-34-CD<b>-SUF</b></kbd>.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>

            <!-- EXPIRES IN -->
            <tr scope="row">
                <th scope="row"><label for="expires_in"><?php esc_html_e('Expires in', 'lmfwc');?></label></th>
                <td>
                    <input name="expires_in" id="expires_in" class="regular-text" type="text">
                    <p class="description" id="tagline-description">
                        <b><?php esc_html_e('Optional.', 'lmfwc');?></b>
                        <span><?php esc_html_e('The number of days for which the license key is valid after purchase. Leave blank if it doesn\'t expire.', 'lmfwc');?></span>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
    <p class="submit">
        <input name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Save' ,'lmfwc');?>" type="submit">
    </p>
</form>
