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
        $store = Zend_Controller_Front::getInstance()->getParam('store');

        $this->view->store = $store;
    }

    /**
     * Executes a query on the triplestore
     *
     * @return  void
     */
    public function queryAction()
    {
        $store = Zend_Controller_Front::getInstance()->getParam('store');

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
        $store = Zend_Controller_Front::getInstance()->getParam('store');

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

}

