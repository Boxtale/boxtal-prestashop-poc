<?php
/**
 * Contains code for cart storage util class.
 */

namespace Boxtal\BoxtalConnectPrestashop\Util;

/**
 * Cart storage util class.
 *
 * Helper to manage cart extra storage.
 */
class CartStorageUtil
{

    /**
     * Get cart storage value.
     *
     * @param int    $cartId      cart id.
     * @param string $key         name of variable.
     * @param int    $shopGroupId shop group id.
     * @param int    $shopId      shop id.
     *
     * @return mixed value
     */
    public static function get($cartId, $key, $shopGroupId, $shopId)
    {
        $sql = new \DbQuery();
        $sql->select('cs.value');
        $sql->from('bx_cart_storage', 'cs');
        $sql->where('cs.id_cart='.(int) $cartId);
        $sql->where('cs.key="'.pSQL($key).'"');
        $sql->where('cs.id_shop_group='.$shopGroupId);
        $sql->where('cs.id_shop='.$shopId);

        $result = \Db::getInstance()->executeS($sql);

        if (isset($result[0]['value'])) {
            return $result[0]['value'];
        }

        return null;
    }

    /**
     * Set cart storage value.
     *
     * @param int          $cartId      cart id.
     * @param string       $key         name of variable.
     * @param string|array $value       value of variable.
     * @param int          $shopGroupId shop group id.
     * @param int          $shopId      shop id.
     *
     * @void
     */
    public static function set($cartId, $key, $value, $shopGroupId, $shopId)
    {
        $data = array(
            'id_cart' => (int) $cartId,
            'id_shop_group' => $shopGroupId,
            'id_shop' => $shopId,
            'key' => pSQL($key),
            'value' => pSQL($value),
        );
        \Db::getInstance()->insert(
            'bx_cart_storage',
            $data,
            true,
            true,
            \DB::REPLACE
        );
    }

    /**
     * Delete obsolete cart storage value.
     *
     * @param int $cartId      cart id.
     * @param int $shopGroupId shop group id.
     * @param int $shopId      shop id.
     *
     * @void
     */
    public static function delete($cartId, $shopGroupId, $shopId)
    {
        \DB::getInstance()->execute(
            'DELETE FROM `'._DB_PREFIX_.'bx_cart_storage` WHERE id_cart="'.$cartId.'" AND id_shop='.$shopId.' AND id_shop_group='.$shopGroupId.';'
        );
    }
}