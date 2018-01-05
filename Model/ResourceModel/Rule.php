<?php
namespace Jh\CoreBugSalesRuleUpgrade\Model\ResourceModel;

use Magento\SalesRule\Model\ResourceModel\Rule as MagentoRule;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\StringUtils;
use Magento\SalesRule\Model\ResourceModel\Coupon;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Framework\App\ObjectManager;

/**
 * @author Anthony Bates <anthony@wearejh.com>
 */
class Rule extends MagentoRule
{

    private $metadataPool;

    public function __construct(
        Context $context,
        StringUtils $string,
        Coupon $resourceCoupon,
        string $connectionName = null,
        DataObject $associatedEntityMapInstance = null,
        Json $serializer = null,
        MetadataPool $metadataPool = null
    ) {
        $this->metadataPool = $metadataPool ?: ObjectManager::getInstance()->get(MetadataPool::class);

        parent::__construct(
            $context,
            $string,
            $resourceCoupon,
            $connectionName,
            $associatedEntityMapInstance,
            $serializer,
            $metadataPool
        );
    }

    /**
     * Adds in if ($data) conditional to fix duplicate primary keys
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param mixed $attributes
     * @return $this
     * @throws \Exception
     */
    public function setActualProductAttributes($rule, $attributes)
    {
        $connection = $this->getConnection();
        $metadata = $this->metadataPool->getMetadata(RuleInterface::class);
        $connection->delete(
            $this->getTable('salesrule_product_attribute'),
            [$metadata->getLinkField() . '=?' => $rule->getData($metadata->getLinkField())]
        );

        //Getting attribute IDs for attribute codes
        $attributeIds = [];
        $select = $this->getConnection()->select()->from(
            ['a' => $this->getTable('eav_attribute')],
            ['a.attribute_id']
        )->where(
            'a.attribute_code IN (?)',
            [$attributes]
        );
        $attributesFound = $this->getConnection()->fetchAll($select);
        if ($attributesFound) {
            foreach ($attributesFound as $attribute) {
                $attributeIds[] = $attribute['attribute_id'];
            }

            $data = [];
            foreach ($rule->getCustomerGroupIds() as $customerGroupId) {
                foreach ($rule->getWebsiteIds() as $websiteId) {
                    foreach ($attributeIds as $attribute) {
                        $data[] = [
                            $metadata->getLinkField() => $rule->getData($metadata->getLinkField()),
                            'website_id' => $websiteId,
                            'customer_group_id' => $customerGroupId,
                            'attribute_id' => $attribute,
                        ];
                    }
                }
            }
            if ($data) {
                $connection->insertMultiple($this->getTable('salesrule_product_attribute'), $data);
            }
        }

        return $this;
    }
}
