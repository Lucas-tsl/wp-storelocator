<?php
/*
Plugin Name: NOVI Store Locator Uploader
Description: Convert CSV files to json and place them into the store local data base
Version: 1.3.3
Author: Lucas DUVERNEUIL
*/

define('NOVI_JSON_DIR', plugin_dir_path(__FILE__) . 'assets/json/');

function getPluginPath(){
    $plugin_file_path = __FILE__;
    $plugin_dir_path = dirname($plugin_file_path);
    
    //var_dump($plugin_dir_path);

    $plugin_path_pend = explode('/public', $plugin_dir_path, 2);
    $plugin_path = $plugin_path_pend[1]."/";

    return $plugin_path;
}

function getPluginPathServer(){
    $plugin_file_path = __FILE__;
    $plugin_dir_path = dirname($plugin_file_path);
    
    //var_dump($plugin_dir_path);

    $plugin_path_pend = explode('/public', $plugin_dir_path, 2);
    $plugin_path = $plugin_path_pend[1]."/";

    return $plugin_path;
}

$plugin_path = getPluginPath();

//var_dump($plugin_path);

//echo "curdir: ".getcwd();

function enqueue_storelocator_script() {
    if (is_admin()) {
        return;
    }

    if (!is_singular()) {
        return;
    }

    global $post;
    if (!$post || !has_shortcode($post->post_content, 'store_locator')) {
        return;
    }

    $plugin_path = plugin_dir_url(__FILE__);
    $settings = updData();
    if (!is_array($settings)) {
        $settings = array();
    }

    $settings = wp_parse_args($settings, array(
        'apikey' => '',
        'btncolor' => '',
        'btncolorbg' => '',
        'ficheurl' => ''
    ));

    $script_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/storelocator.js');

    // Enqueue votre script JavaScript
    wp_enqueue_script('storelocator', $plugin_path . 'assets/js/storelocator.js', array(), $script_version, true);
    wp_localize_script('storelocator', 'storelocator_vars', array(
        'plugin_url' => $plugin_path,
        'settings' => $settings,
    ));

    // Ajoutez l'attribut type="module" au script
    add_filter('script_loader_tag', function($tag, $handle, $src) {
        if ($handle === 'storelocator') {
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }, 10, 3);
}
add_action('wp_enqueue_scripts', 'enqueue_storelocator_script');



// Fonction qui génère le HTML à insérer avec le shortcode
function shortcode_html() {

    $plugin_path = getPluginPath();

    //$plugin_path = substr($plugin_path_pend[1], 0, -1);

    //$plugin_path = $plugin_dir_path;

    //$plugin_path = $plugin_path_pend[1];
    // Remplacez ceci par le HTML que vous souhaitez afficher
    $html_content = "<link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' integrity='sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=' crossorigin=''><script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js' integrity='sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=' crossorigin=''></script><link rel='stylesheet' href='".$plugin_path . 'assets/css/storelocator.css'."'><div class='store-locator'><style>.single_post_title.text_left{padding:0;} p{margin:0;}</style><div class='main-cont'><div class='search-fields'><input id='searchInput' type='text' placeholder='Entrez votre ville, code postal...' /><div id='searchResults'></div><div id='loadingMessage' style='display: none;'>Recherche en cours...</div></div><div class='map-cont flex'><div id='map'></div><div id='listinfo' class='listinfo'><!--<div class='card' id='card-1' onclick=''><div class='card-title' id='card-title-1'>City Name</div><div class='card-subtitle' id='card-subtitle-1'>Ligne 5 city</div><a href='' class='btn'>J'Y VAIS</a></div>--></div></div></div></div>";

    return $html_content;
}
// Enregistrement du shortcode
add_shortcode('store_locator', 'shortcode_html');

// ------------------ FICHE MAGASIN (SEO/GEO) ------------------

// Autorise ?magasin=<id_store> comme query var WordPress reconnue
add_filter('query_vars', function ($vars) {
    $vars[] = 'magasin';
    return $vars;
});

function novi_get_store_by_id($id_store) {
    $json_path = NOVI_JSON_DIR . 'stores.json';
    if (!file_exists($json_path)) {
        return null;
    }

    $stores = json_decode(file_get_contents($json_path), true);
    if (!is_array($stores)) {
        return null;
    }

    foreach ($stores as $store) {
        if (isset($store['id_store']) && (string) $store['id_store'] === (string) $id_store) {
            return $store;
        }
    }

    return null;
}

// Enqueue Leaflet + mini-carte uniquement sur la page contenant [fiche_magasin]
function enqueue_fiche_magasin_assets() {
    if (is_admin() || !is_singular()) {
        return;
    }

    global $post;
    if (!$post || !has_shortcode($post->post_content, 'fiche_magasin')) {
        return;
    }

    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

    $plugin_path = plugin_dir_url(__FILE__);
    wp_enqueue_style('storelocator', $plugin_path . 'assets/css/storelocator.css', array(), filemtime(plugin_dir_path(__FILE__) . 'assets/css/storelocator.css'));

    $settings = updData();
    if (!is_array($settings)) {
        $settings = array();
    }
    $settings = wp_parse_args($settings, array('apikey' => ''));

    $script_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/fiche-magasin.js');
    wp_enqueue_script('fiche-magasin', $plugin_path . 'assets/js/fiche-magasin.js', array('leaflet'), $script_version, true);
    wp_localize_script('fiche-magasin', 'fiche_magasin_vars', array(
        'apikey' => $settings['apikey'],
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_fiche_magasin_assets');

// Titre de la page adapté au magasin affiché (SEO)
add_filter('document_title_parts', function ($title_parts) {
    if (!is_singular() || get_query_var('magasin') === '') {
        return $title_parts;
    }

    $store = novi_get_store_by_id(get_query_var('magasin'));
    if ($store && !empty($store['name'])) {
        $title_parts['title'] = $store['name'] . ' – Les Senteurs Gourmandes';
    }

    return $title_parts;
});

function fiche_magasin_shortcode() {
    $id_store = get_query_var('magasin');
    if ($id_store === '' || $id_store === false) {
        $id_store = isset($_GET['magasin']) ? sanitize_text_field(wp_unslash($_GET['magasin'])) : '';
    }

    $retour_url = home_url('/ou-nous-trouver/');

    if ($id_store === '') {
        return "<div class='fiche-magasin fiche-magasin-empty'><p>Sélectionnez un magasin depuis <a href='" . esc_url($retour_url) . "'>la carte des magasins</a> pour afficher sa fiche.</p></div>";
    }

    $store = novi_get_store_by_id($id_store);

    if (!$store) {
        return "<div class='fiche-magasin fiche-magasin-empty'><p>Ce magasin est introuvable. <a href='" . esc_url($retour_url) . "'>Retourner à la carte des magasins</a>.</p></div>";
    }

    $name = isset($store['name']) ? $store['name'] : '';
    $address1 = isset($store['address1']) ? $store['address1'] : '';
    $address2 = isset($store['address2']) ? $store['address2'] : '';
    $postcode = isset($store['postcode']) ? $store['postcode'] : '';
    $city = isset($store['city']) ? $store['city'] : '';
    $country = isset($store['country']) ? $store['country'] : '';
    $phone = isset($store['phone']) ? $store['phone'] : '';
    $lat = isset($store['latitude']) ? $store['latitude'] : '';
    $lon = isset($store['longitude']) ? $store['longitude'] : '';

    // Champ optionnel : à ajouter au Google Sheet/CSV quand le contenu éditorial sera rédigé
    $description = !empty($store['description'])
        ? $store['description']
        : sprintf(
            "Découvrez l'univers Les Senteurs Gourmandes chez %s, %s. Parfums d'ambiance, bougies et idées cadeaux à retrouver en magasin.",
            $name,
            $city
        );

    $map_id = 'fiche-map-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id_store);

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => $name,
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => trim($address1 . ' ' . $address2),
            'postalCode' => $postcode,
            'addressLocality' => $city,
            'addressCountry' => $country,
        ),
    );
    if ($lat !== '' && $lon !== '') {
        $schema['geo'] = array(
            '@type' => 'GeoCoordinates',
            'latitude' => $lat,
            'longitude' => $lon,
        );
    }
    if ($phone) {
        $schema['telephone'] = $phone;
    }

    ob_start();
    ?>
    <div class="fiche-magasin">
        <div class="fiche-magasin-header">
            <h1><?php echo esc_html($name); ?> &ndash; Les Senteurs Gourmandes</h1>
            <p class="fiche-magasin-address">
                <?php echo esc_html($address1); ?><?php if ($address2) : ?>, <?php echo esc_html($address2); ?><?php endif; ?><br>
                <?php echo esc_html(trim($postcode . ' ' . $city)); ?><?php if ($country) : ?>, <?php echo esc_html($country); ?><?php endif; ?>
                <?php if ($phone) : ?><br>Tél. : <?php echo esc_html($phone); ?><?php endif; ?>
            </p>
        </div>

        <div class="fiche-magasin-intro">
            <p><?php echo esc_html($description); ?></p>
        </div>

        <div class="fiche-magasin-products">
            <h2>Nos produits phares en magasin</h2>
            <?php
            // Point d'extension : un thème/plugin tiers peut injecter ici une sélection de produits par magasin
            $products_html = apply_filters('novi_fiche_magasin_products', '', $store);
            if ($products_html !== '') {
                echo $products_html;
            } else {
                echo '<p>Parfums d\'ambiance, bougies gourmandes et coffrets cadeaux vous attendent chez Les Senteurs Gourmandes ' . esc_html($city) . '.</p>';
            }
            ?>
        </div>

        <div class="fiche-magasin-bottom">
            <?php if ($lat !== '' && $lon !== '') : ?>
            <div class="fiche-magasin-map">
                <h2>Accès</h2>
                <div id="<?php echo esc_attr($map_id); ?>" class="fiche-magasin-map-canvas" data-lat="<?php echo esc_attr($lat); ?>" data-lon="<?php echo esc_attr($lon); ?>" data-name="<?php echo esc_attr($name); ?>"></div>
            </div>
            <?php endif; ?>
            <div class="fiche-magasin-faq">
                <h2>Questions fréquentes</h2>
                <div class="faq-item">
                    <p class="faq-q">Comment se rendre chez <?php echo esc_html($name); ?> ?</p>
                    <p class="faq-a">Rendez-vous au <?php echo esc_html(trim($address1 . ', ' . $postcode . ' ' . $city)); ?>. Utilisez le bouton "J'y vais" depuis la carte des magasins pour lancer votre itinéraire.</p>
                </div>
                <?php if ($phone) : ?>
                <div class="faq-item">
                    <p class="faq-q">Comment contacter ce magasin ?</p>
                    <p class="faq-a">Vous pouvez appeler le <?php echo esc_html($phone); ?>.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <p class="fiche-magasin-back"><a href="<?php echo esc_url($retour_url); ?>">&larr; Retour à la carte des magasins</a></p>

        <script type="application/ld+json"><?php echo wp_json_encode($schema); ?></script>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('fiche_magasin', 'fiche_magasin_shortcode');

// Créer une page d'administration pour l'upload CSV
function custom_csv_upload_page() {
    add_menu_page('NOVI Store Locator Uploader', 'NOVI Store Locator Uploader', 'manage_options', 'csv_upload_page', 'csv_upload_page_callback');
    add_submenu_page(
        'csv_upload_page',
        'Paramètres',
        'Paramètres',
        'manage_options',
        'storelocator-page2',
        'storelocator_page2'
    );
}
add_action('admin_menu', 'custom_csv_upload_page');

function arr_to_json($data){

    $json_directory = plugin_dir_path(__FILE__) . 'assets/json/settings/';
    $json_filename = 'settings.json';

    // Chemin du fichier JSON à créer
    $chemin_fichier_json = $json_directory.$json_filename;

    // Convertir le tableau en format JSON
    $json_data = json_encode($data);

    // Écrire le contenu JSON dans le fichier
    $resultat = file_put_contents($chemin_fichier_json, $json_data);

    // Vérifiez si l'écriture a réussi ou non
    if ($resultat !== false) {
        echo "<div class='done2'>Les données JSON ont été écrites avec succès dans le fichier.</div>";
    } else {
        // Récupérez les erreurs de dernière erreur PHP
        $error = error_get_last();
    
        // Affichez le message d'erreur
        echo "<div class='err2'>Une erreur est survenue dans l'écriture du fichier : ".$error."</div>";
    }

}

function updData(){

    $plugin_path = getPluginPath();

    $json_directory = plugin_dir_path(__FILE__) . 'assets/json/settings/'; //on est dans public/wp-admin donc on doit revenir en arrière dans public/ pour aller dans wp-content
    $json_filename = 'settings.json';

    // Chemin du fichier JSON à créer
    $chemin_fichier_json = $json_directory.$json_filename;

    // Lire le contenu du fichier JSON
    $json_contenu = file_get_contents($chemin_fichier_json);

    // Décoder le JSON en un tableau associatif
    $data = json_decode($json_contenu, true);

    return $data;

}

if(isset($_POST['submit'])){
    if(isset($_POST['apikey'])){
        if(!empty($_POST['apikey'])){
            $data = array(
                'apikey' => $_POST['apikey'],
                'btncolor' => $_POST['btncolor'],
                'btncolorbg' => $_POST['btncolorbg'],
                'ficheurl' => isset($_POST['ficheurl']) ? esc_url_raw($_POST['ficheurl']) : ''
            );
            arr_to_json($data);
        }
    }

}

// Callback pour afficher le formulaire d'upload
function csv_upload_page_callback() {

    $json_directory = plugin_dir_path(__FILE__) . 'assets/json/';
    $old_json_filename = 'old_stores.json';
    $new_json_filename = 'stores.json';

    // Vérifier si le fichier "old_stores.json" existe pour griser le bouton
    $is_old_stores_exist = file_exists($json_directory . $old_json_filename);


    ?>
    <style>
        .err{
            margin: 30 0 0 183px;
            background:#cf635b;
            color:#fff;
            padding:5px 10px;
            width: calc(100% - 220px);
        }
        .done{
            margin: 30 0 0 183px;
            background:#6acf5b;
            color:#fff;
            padding:5px 10px;
            width: calc(100% - 220px);
        }
    </style>
    <div class="wrap">
        <h2>NOVI Store Locator Uploader</h2>
        <p>
            Rendez-vous sur le fichier Google Sheet des magasins. <br>
            Effectuez vos ajouts/modifications<br>
            /!\ Ne touchez jamais à la 1ère ligne <br>
            Une fois vos modifications terminées, faites : fichier > Télécharger > Valeurs séparées par des virgules (CSV)<br>
            Puis, sur cette page, cliquez sur le bouton "Choisir un fichier", puis sélectionnez le fichier que vous venez de télécharger.<br>
            Puis, appuyez sur "Upload"<br>
            Patientez. La conversion peut prendre plusieurs minutes.<br><br>
            <br>
            Pour intégrer le store locator, vous n'avez plus qu'à créer une nouvelle page, et y insérer le short code suivant : [store_locator]
        </p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" />
            <?php wp_nonce_field('csv_upload_nonce', 'csv_upload_nonce'); ?>
            <input type="submit" name="submit" value="Upload" />
            <?php if ($is_old_stores_exist) : ?>
                <input type="submit" name="revert" value="Revenir à la version précédente" onclick="return confirm('Êtes-vous sûr de vouloir revenir à la version précédente ?');" />
            <?php else : ?>
                <input type="submit" name="revert" value="Revenir à la version précédente" disabled="disabled" />
            <?php endif; ?>
        </form>

        <!--<div class="spacer" style="border-top:1px solid gray; width:100%; height:2px; margin: 20px 0;"></div>-->
    </div>
    <?php
}

// Gérer l'upload du fichier CSV et sa conversion en JSON
function handle_csv_upload() {

    $json_directory = plugin_dir_path(__FILE__) . 'assets/json/';
    $old_json_filename = 'old_stores.json';
    $new_json_filename = 'stores.json';
    
    if (isset($_POST['submit'])) {
        if (!isset($_FILES['csv_file'])) {
            echo '<div class="err">Fichier manquant</div>';
            return;
        }

        $file_name = $_FILES['csv_file']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

        // Vérifier si le fichier est au format CSV
        if (strtolower($file_ext) !== 'csv') {
            echo '<div class="err">Le fichier n\'est pas au format CSV</div>';
            return;
        }

        if (!wp_verify_nonce($_POST['csv_upload_nonce'], 'csv_upload_nonce')) {
            wp_die('Security check');
        }

        $uploaded_file = $_FILES['csv_file'];

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($movefile && empty($movefile['error'])) {
            // Convertir le fichier CSV en JSON
            $csv_file = $movefile['file'];
            $json_data = convert_csv_to_json($csv_file);

            // Vérifier si le fichier "stores.json" existe et le renommer en "old_stores.json"
            if (file_exists($json_directory . $new_json_filename)) {
                rename($json_directory . $new_json_filename, $json_directory . $old_json_filename);
            }

            // Enregistrer le nouveau fichier JSON
            file_put_contents($json_directory . $new_json_filename, $json_data);

            echo '<div class="done">Le fichier CSV a été uploadé et converti en JSON avec succès. </div>';
        } else {
            echo '<div class="err">Erreur lors de l\'upload du fichier : ' . $movefile['error'] . '</div>';
        }
    }
        // Gérer la suppression et le renommage des fichiers JSON
        if (isset($_POST['revert'])) {

            if (file_exists($json_directory . $new_json_filename)) {
                unlink($json_directory . $new_json_filename);
            }
            if (file_exists($json_directory . $old_json_filename)) {
                rename($json_directory . $old_json_filename, $json_directory . $new_json_filename);
                echo '<div class="done">La version précédente à été restaurée avec succès.</div>';
            } else {
                echo '<div class="err">Le fichier old_stores.json n\'existe pas, restauration de la sauvegarde impossible.</div>';
            }
        }
}


add_action('admin_init', 'handle_csv_upload');

// Fonction pour convertir le fichier CSV en JSON
function convert_csv_to_json($csv_file) {
    $file_handle = fopen($csv_file, 'r');
    $json_data = array();

    if ($file_handle) {
        $headers = fgetcsv($file_handle); // Récupérer la première ligne comme en-têtes

        while (($data = fgetcsv($file_handle)) !== false) {
            if (count($data) !== count($headers)) {
    continue; // Ignorer les lignes mal formatées
}
            $entry = array();
            foreach ($headers as $index => $header) {
                $entry[$header] = $data[$index];
            }
            $json_data[] = $entry;
        }

        fclose($file_handle);

        return json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        return false;
    }
}




function storelocator_page2() {
    // Contenu de la deuxième page
    echo '<h1>Modifier paramètres</h1>';

    $data = updData();

    ?>

    <style>
        .err2{
            margin: 30 0 0 183px;
            background:#cf635b;
            color:#fff;
            padding:5px 10px;
            width: calc(100% - 220px);
        }
        .done2{
            margin: 30 0 0 183px;
            background:#6acf5b;
            color:#fff;
            padding:5px 10px;
            width: calc(100% - 220px);
        }
    </style>

    <form method="post" action="">

        <table class="form-table">
            <tr valign="top">
                <th scope="row">Clé API MapTiler :</th>
                <td>
                    <input type="text" name="apikey" value="<?php echo $data['apikey']; ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Couleur texte des boutons : (hexadecimal)</th>
                <td>
                    <input type="text" name="btncolor" value="<?php echo $data['btncolor']; ?>" placeholder="ex: #ff0000" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Couleur fond des boutons : (hexadecimal)</th>
                <td>
                    <input type="text" name="btncolorbg" value="<?php echo $data['btncolorbg']; ?>" placeholder="ex: #ff0000" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">URL de la page fiche magasin :</th>
                <td>
                    <input type="text" name="ficheurl" value="<?php echo isset($data['ficheurl']) ? esc_attr($data['ficheurl']) : ''; ?>" placeholder="ex: https://lessenteursgourmandes.fr/fiche-magasin/" style="width:400px;" />
                    <p class="description">Page contenant le shortcode [fiche_magasin]. Le bouton "Je découvre" y renverra avec le paramètre ?magasin=&lt;id_store&gt;.</p>
                </td>
            </tr>
        </table>

        <input type="submit" name="submit" value="Enregistrer" class="button button-primary" />
    </form>


    <?php
}
