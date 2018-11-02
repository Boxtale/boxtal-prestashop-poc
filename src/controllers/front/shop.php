<?php
/**
 * Contains code for the shop rest controller.
 */

use Boxtal\BoxtalConnectPrestashop\Util\ShopUtil;
use Boxtal\BoxtalPhp\RestClient;
use Boxtal\BoxtalConnectPrestashop\Controllers\Misc\NoticeController;
use Boxtal\BoxtalConnectPrestashop\Util\ApiUtil;
use Boxtal\BoxtalConnectPrestashop\Util\AuthUtil;
use Boxtal\BoxtalConnectPrestashop\Util\ConfigurationUtil;

/**
 * Shop class.
 *
 * Opens API endpoint to pair.
 */
class boxtalconnectShopModuleFrontController extends ModuleFrontController
{

    /**
     * Processes request.
     *
     * @void
     */
    public function postProcess()
    {

        $entityBody = file_get_contents('php://input');

        AuthUtil::authenticate($entityBody);
        $body = AuthUtil::decryptBody($entityBody);

        if (null === $body) {
            ApiUtil::sendApiResponse(400);
        }

        $route = Tools::getValue('route'); // Get route

        if ('pair' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case RestClient::$POST:
                        $this->pairingHandler($body);
                        break;

                    default:
                        break;
                }
            }
        } elseif ('update-configuration' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case RestClient::$POST:
                        $this->updateConfigurationHandler($body);
                        break;

                    default:
                        break;
                }
            }
        } elseif ('delete-configuration' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case RestClient::$POST:
                        $this->deleteConfigurationHandler($body);
                        break;

                    default:
                        break;
                }
            }
        }
        ApiUtil::sendApiResponse(400);
    }

    /**
     * Endpoint callback.
     *
     * @param array $body request body.
     *
     * @void
     */
    public function pairingHandler($body)
    {

        if (null === $body) {
            ApiUtil::sendApiResponse(400);
        }

        $accessKey   = null;
        $secretKey   = null;
        $callbackUrl = null;
        if (is_object($body) && property_exists($body, 'accessKey') && property_exists($body, 'secretKey')) {
            //phpcs:ignore
            $accessKey = $body->accessKey;
            //phpcs:ignore
            $secretKey = $body->secretKey;

            if (property_exists($body, 'pairCallbackUrl')) {
                //phpcs:ignore
                $callbackUrl = $body->pairCallbackUrl;
            }
        }

        if (null !== $accessKey && null !== $secretKey) {
            if (! AuthUtil::isPluginPaired(ShopUtil::$shopGroupId, ShopUtil::$shopId)) { // initial pairing.
                AuthUtil::pairPlugin($accessKey, $secretKey);
                NoticeController::removeNotice(NoticeController::$setupWizard, ShopUtil::$shopGroupId, ShopUtil::$shopId);
                NoticeController::addNotice(NoticeController::$pairing, ShopUtil::$shopGroupId, ShopUtil::$shopId, array( 'result' => 1 ));
                ApiUtil::sendApiResponse(200);
            } else { // pairing update.
                if (null !== $callbackUrl) {
                    AuthUtil::pairPlugin($accessKey, $secretKey);
                    NoticeController::removeNotice(NoticeController::$pairing, ShopUtil::$shopGroupId, ShopUtil::$shopId);
                    AuthUtil::startPairingUpdate($callbackUrl);
                    NoticeController::addNotice(NoticeController::$pairingUpdate, ShopUtil::$shopGroupId, ShopUtil::$shopId);
                    ApiUtil::sendApiResponse(200);
                } else {
                    ApiUtil::sendApiResponse(403);
                }
            }
        } else {
            NoticeController::addNotice(NoticeController::$pairing, ShopUtil::$shopGroupId, ShopUtil::$shopId, array( 'result' => 0 ));
            ApiUtil::sendApiResponse(400);
        }
    }

    /**
     * Endpoint callback.
     *
     * @param object $body request body.
     *
     * @void
     */
    public function deleteConfigurationHandler($body)
    {
        if (null === $body) {
            ApiUtil::sendApiResponse(400);
        }

        if (! is_object($body) || ! property_exists($body, 'accessKey') || $body->accessKey !== AuthUtil::getAccessKey(ShopUtil::$shopGroupId, ShopUtil::$shopId)) {
            ApiUtil::sendApiResponse(403);
        }

        ConfigurationUtil::deleteConfiguration();
        ApiUtil::sendApiResponse(200);
    }

    /**
     * Endpoint callback.
     *
     * @param object $body request body.
     *
     * @void
     */
    public function updateConfigurationHandler($body)
    {
        if (null === $body) {
            ApiUtil::sendApiResponse(400);
        }

        if (ConfigurationUtil::parseConfiguration($body)) {
            ApiUtil::sendApiResponse(200);
        }

        ApiUtil::sendApiResponse(400);
    }
}
