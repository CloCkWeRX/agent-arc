<?php
/**
 * Agent
 *
 * PHP 5
 *
 * @category Semantic_Web
 * @package  Agent
 * @author   Daniel O'Connor <daniel.oconnor@gmail.com>
 * @license  BSD <http://www.opensource.org/licenses/bsd-license.php>
 * @link     http://code.google.com/p/xmlgrddl
 */

/**
 * A controller to interact with the triplestore.
 *
 * @category Semantic_Web
 * @package  Agent
 * @author   Daniel O'Connor <daniel.oconnor@gmail.com>
 * @license  BSD <http://www.opensource.org/licenses/bsd-license.php>
 * @link     http://code.google.com/p/xmlgrddl
 */
class SparqlController extends Zend_Controller_Action
{
    /**
     * Displays index
     *
     * @return  void
     */
    public function indexAction()
    {
        $store = $this->getStore($this->getConfig());

        $this->view->store = $store;
    }

    /**
     * Executes a query on the triplestore
     *
     * @return  void
     */
    public function queryAction()
    {
        $store = $this->getStore($this->getConfig());

        $query = $this->_request->getParam('query');

        $result = $store->query($query, 'rows');

        if (!$result) {
            $this->view->messages[] = "Query failed for some reason.";
        }

        $this->view->result = $result;
        $this->view->query  = $query;
        $this->view->store  = $store;
    }

    /**
     * Loads data into triplestore
     *
     * @return  void
     */
    public function loadAction()
    {
        $store = $this->getStore($this->getConfig());

        $url = $this->_request->getParam('url');

        $result = $store->query('LOAD <' . $url . '>');

        if (!$result) {
            $this->view->messages[] = "Query failed for some reason.";
        }

        $this->view->result = $result;
        $this->view->query  = $query;
        $this->view->store  = $store;
    }

    /*
    public function endPointAction()
    {
        // See http://arc.semsol.org/docs/v2/endpoint


        $config = array(

          'db_host' => 'localhost',
          'db_name' => 'my_db',
          'db_user' => 'user',
          'db_pwd' => 'secret',


          'store_name' => 'my_endpoint_store',


          'endpoint_features' => array(
            'select', 'construct', 'ask', 'describe',
            'load', 'insert', 'delete',
            'dump'
          ),
          'endpoint_timeout' => 60,
          'endpoint_read_key' => '',
          'endpoint_write_key' => 'somekey',
          'endpoint_max_limit' => 250,
        );

        $ep = ARC2::getStoreEndpoint($config);

        if (!$ep->isSetUp()) {
          $ep->setUp();
        }

        $ep->go();

            }
        */

    /**
     * Fetch an instance of the Triplestore.
     *
     * @param Zend_Config_Ini $config Application Configuration
     *
     * @todo    Refactor
     * @return  ARC2
     */
    protected function getStore(Zend_Config $config)
    {
        $store = ARC2::getStore(array(
                                'db_name' => $config->arc->db->name,
                                'db_user' => $config->arc->db->username,
                                'db_pwd' =>  $config->arc->db->password,
                                'db_host' =>  $config->arc->db->host,
                                'store_name' => $config->arc->db->name
                                ));

        return $store;
    }

    /**
     * Fetch config
     *
     * @todo    Refactor
     * @return  Zend_Config_Ini
     */
    protected function getConfig()
    {
        $config_path = dirname(__FILE__) . '/../config/default.ini';
        return new Zend_Config_Ini($config_path, 'default');
    }

}

