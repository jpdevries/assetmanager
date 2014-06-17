<?php
/**
 * This HTML controller is what generates HTML pages (as opposed to JSON responses
 * generated by the other controllers).  The reason is testability: most of the 
 * manager app can be tested by $scriptProperties in, JSON out.  The HTML pages
 * generated by this controller end up being static HTML pages (well... ideally, 
 * anyway). 
 *
 * See http://stackoverflow.com/questions/10941249/separate-rest-json-api-server-and-client
 *
 * See the IndexManagerController class (index.class.php) for routing info.
 *
 * @package assman
 */
namespace Assman;
class PageController extends BaseController {

    public $loadHeader = false;
    public $loadFooter = false;
    // GFD... this can't be set at runtime. See improvised addStandardLayout() function
    public $loadBaseJavascript = false; 
    // Stuff needed for interfacing with Assman API (mapi)
    public $client_config = array();
    
    function __construct(\modX &$modx,$config = array()) {
        parent::__construct($modx,$config);
        static::$x =& $modx;

        $this->config['controller_url'] = self::url();
        $this->config['core_path'] = $this->modx->getOption('assman.core_path', null, MODX_CORE_PATH.'components/assman/');
        $this->config['assets_url'] = $this->modx->getOption('assman.assets_url', null, MODX_ASSETS_URL.'components/assman/');

//        $this->modx->regClientCSS($this->config['assets_url'].'css/mgr.css');
//        $this->modx->regClientCSS('//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');
//        $this->modx->regClientStartupScript($this->config['assets_url'].'js/jquery.min.js');
//        $this->modx->regClientStartupScript($this->config['assets_url'].'js/jquery-ui.js'); 
        
        
        
        $this->modx->regClientCSS($this->config['assets_url'] . 'css/mgr.css');
        $this->modx->regClientCSS($this->config['assets_url'] . 'css/dropzone.css');
//        $this->modx->regClientCSS($this->config['assets_url'].'css/datepicker.css');
        $this->modx->regClientCSS('//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/jquery.min.js');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/jquery-ui.js');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/handlebars.js');
        //$this->modx->regClientStartupScript($this->config['assets_url'].'js/app.js');
        
//        $this->modx->regClientStartupScript($this->config['assets_url'].'js/jquery.tabify.js');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/dropzone.js');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/bootstrap.js');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/multisortable.js');        
    }


    

    
    //------------------------------------------------------------------------------
    //! Assets
    //------------------------------------------------------------------------------
    /**
     * Asset management main page
     *
     * @param array $scriptProperties
     */
    public function getAssets(array $scriptProperties = array()) {
        $this->modx->log(\modX::LOG_LEVEL_INFO, print_r($scriptProperties,true),'','Asset Manager PageController:'.__FUNCTION__);
        $Obj = new Asset($this->modx);
        $results = $Obj->all($scriptProperties);
        $this->setPlaceholder('results', $results);
        $this->setPlaceholders($scriptProperties);
        return $this->fetchTemplate('main/assets.php');
    }

    public function postAssets(array $scriptProperties = array()) {
        return $this->getAssets($scriptProperties);
    }
 
     public function getAssetCreate(array $scriptProperties = array()) {
        $this->modx->log(\modX::LOG_LEVEL_INFO, print_r($scriptProperties,true),'','Asset Manager PageController:'.__FUNCTION__);
        $Obj = new Asset($this->modx);
        $results = $Obj->all($scriptProperties);
        $this->setPlaceholder('results', $results);
        $this->setPlaceholders($scriptProperties);
        return $this->fetchTemplate('asset/create.php');
    }    

    public function getAssetEdit(array $scriptProperties = array()) {
        $this->modx->log(\modX::LOG_LEVEL_INFO, print_r($scriptProperties,true),'','Asset Manager PageController:'.__FUNCTION__);
        $asset_id = (int) $this->modx->getOption('asset_id',$scriptProperties);
        $Obj = new Asset($this->modx);    
        if (!$result = $Obj->find($asset_id)) {
            return $this->sendError('Page not found.');
        }
        $this->setPlaceholders($scriptProperties);
        $this->setPlaceholders($result->toArray());
        $this->setPlaceholder('result',$result);
        return $this->fetchTemplate('asset/edit.php');
    }

    
    /**
     * 
     * @param array $scriptProperties
     */
    public function getIndex(array $scriptProperties = array()) {
        $this->modx->log(\modX::LOG_LEVEL_INFO, print_r($scriptProperties,true),'','Asset Manager PageController:'.__FUNCTION__);
        return $this->fetchTemplate('main/index.php');
    }

    
    //------------------------------------------------------------------------------
    //! Settings
    //------------------------------------------------------------------------------
    /**
     * @param array $scriptProperties
     */
    public function getSettings(array $scriptProperties = array()) {
        $this->modx->log(\modX::LOG_LEVEL_INFO, print_r($scriptProperties,true),'','Asset Manager PageController:'.__FUNCTION__);
        return $this->fetchTemplate('main/settings.php');
     
    }

    public function getTest(array $scriptProperties = array()) {
        return 'Test...';
    }
    
    //------------------------------------------------------------------------------
    //! Page
    //------------------------------------------------------------------------------
    /**
     * Generates a tab for Ext JS editing a resource
     * @param array $scriptProperties
     */
    public function getPageAssetsTab(array $scriptProperties = array()) {
        $this->modx->setLogLevel(4);
        $this->modx->log(\modX::LOG_LEVEL_INFO, print_r($scriptProperties,true),'','Asset Manager PageController:'.__FUNCTION__);
        $page_id = (int) $this->modx->getOption('page_id', $scriptProperties);
        $this->config['page_id'] = $page_id;
        $this->setPlaceholder('page_id', $page_id);
        $this->scriptProperties['_nolayout'] = true;

        $A = new Asset($this->modx);
        $c = $this->modx->newQuery('PageAsset');
        $c->where(array('PageAsset.page_id' => $page_id));
        $c->sortby('PageAsset.seq','ASC');
        $PA = $this->modx->getCollectionGraph('PageAsset','{"Asset":{}}',$c);
        $json = array();
        $order = array();
        $groups = array();
        foreach ($PA as $p) {
            $array = $p->Asset->toArray();
            $array['group'] = $p->get('group');
            $array['is_active'] = $p->get('is_active');
            $json[ $p->get('asset_id') ] = $array;            
            $order[] = $p->get('asset_id');
            $groups[ $p->get('group') ] = true;
        }
        $groups = array_keys($groups);
        sort($groups);
        
        if ($json) {
            $Assets = json_encode($json);
        }
        else {
            $Assets = '{}';
        }

        $path = $this->modx->getOption('assman.core_path','', MODX_CORE_PATH.'components/assman/').'views/';
        $out = file_get_contents($path.'main/pageassets.tpl');

        // Wedge the output into the tab
        $this->modx->lexicon->load('assman:default');
        $title = $this->modx->lexicon('assets_tab');

        $this->modx->regClientStartupHTMLBlock('<script type="text/javascript">
            var assman = '.json_encode($this->config).';
            var Assets = '.$Assets.';
            var Order = '.json_encode($order).';
            var Groups = '.json_encode($groups).';
            var inited = 0;
            MODx.on("ready",function() {
                console.log("[assman] on ready...");
                MODx.addTab("modx-resource-tabs",{
                    title: '.json_encode($title).',
                    id: "assets-resource-tab",
                    width: "95%",
                    html: '.json_encode($out).'
                });
                if (inited==0) {
                    page_init();
                }
            });                
        </script>');
        $this->modx->regClientStartupScript($this->config['assets_url'].'js/app.js');

    }            
}
/*EOF*/