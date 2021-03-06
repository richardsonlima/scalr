<?php
namespace Scalr\Service\Aws\Ec2\DataType;

use Scalr\Service\Aws\Ec2Exception;

/**
 * AvailabilityZoneFilterData
 *
 * @author   Vitaliy Demidov   <vitaliy@scalr.com>
 * @since    26.12.2012
 *
 * @property \Scalr\Service\Aws\Ec2\DataType\AvailabilityZoneFilterNameType $name
 *           A filter key name
 *
 * @property array $value
 *           An array of values
 *
 * @method   \Scalr\Service\Aws\Ec2\DataType\AvailabilityZoneFilterNameType getName()
 *           getName()
 *           Gets filter key name.
 *
 * @method   void __construct()
 *           __construct(\Scalr\Service\Aws\Ec2\DataType\AvailabilityZoneFilterNameType|string $name, string|array $value)
 *           Constructor
 *
 * @method   array getValue()
 *           getValue()
 *           Gets list of values.
 *
 * @method   \Scalr\Service\Aws\Ec2\DataType\AvailabilityZoneFilterData setName()
 *           setName(\Scalr\Service\Aws\Ec2\DataType\AvailabilityZoneFilterNameType|string $name)
 *           Sets filter key name.
 *
 * @method   \Scalr\Service\Aws\Ec2\DataType\AvailabilityZoneFilterData setValue()
 *           setName(string|array $value)
 *           Sets value or the list of the values.
 */
class AvailabilityZoneFilterData extends AbstractFilterData
{

    /**
     * {@inheritdoc}
     * @see Scalr\Service\Aws\Ec2\DataType.AbstractFilterData::getFilterNameTypeClass()
     */
    public function getFilterNameTypeClass()
    {
        return __NAMESPACE__ . '\\AvailabilityZoneFilterNameType';
    }
}