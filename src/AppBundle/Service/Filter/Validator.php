<?php
// src/AppBundle/Service/Filter/Validator.php
namespace AppBundle\Service\Filter;

use Symfony\Component\HttpFoundation\Request;

use AppBundle\Service\Filter\Utility\Interfaces\FilterArgumentsInterface,
    AppBundle\Entity\Estate,
    AppBundle\Entity\EstateFeatures;

class Validator implements FilterArgumentsInterface
{
    private $_availableValuesExtractor;

    public function setAvailableValuesExtractor(AvailableValuesExtractor $availableValuesExtractor)
    {
        $this->_availableValuesExtractor = $availableValuesExtractor;
    }

    public function validateArguments(array $filterArguments, array $estates, $currency)
    {
        if( !empty($filterArguments[self::FILTER_DISTRICTS]) )
        {
            if( !$this->validateDistricts($filterArguments[self::FILTER_DISTRICTS]) )
                return FALSE;
        }

        if( !empty($filterArguments[self::FILTER_ESTATE_TYPE]) )
        {
            if( !$this->validateEstateType($filterArguments[self::FILTER_ESTATE_TYPE], $estates) )
                return FALSE;
        }

        if( !empty($filterArguments[self::FILTER_TRADE_TYPE]) )
        {
            if( !$this->validateTradeType($filterArguments[self::FILTER_TRADE_TYPE], $estates) )
                return FALSE;
        }

        if( !empty($filterArguments[self::FILTER_CURRENCY]) )
        {
            if( !$this->validateCurrency($filterArguments[self::FILTER_CURRENCY]) )
                return FALSE;
        }

        if( !empty($filterArguments[self::FILTER_PRICE]) )
        {
            $filterArguments[self::FILTER_PRICE] = $this->sanitizePriceRange($filterArguments[self::FILTER_PRICE], $estates, $currency);
        }

        if( !empty($filterArguments[self::FILTER_SPACE]) )
        {
            $filterArguments[self::FILTER_SPACE] = $this->sanitizeSpaceRange($filterArguments[self::FILTER_SPACE], $estates);
        }

        if( !empty($filterArguments[self::FILTER_ATTRIBUTES]) )
        {
            $filterArguments[self::FILTER_ATTRIBUTES] = $this->sanitizeAttributes($filterArguments[self::FILTER_ATTRIBUTES], $estates);
        }

        if( !empty($filterArguments[self::FILTER_FEATURES]) )
        {
            if( !$this->validateFeatures($filterArguments[self::FILTER_FEATURES]) )
                return FALSE;
        }

        return $filterArguments;
    }

    protected function validateEstateType($estateType, $estates)
    {
        $existingEstateTypes = $this->_availableValuesExtractor->availableEstateTypes($estates, $flatValues = TRUE);

        if( !in_array($estateType, $existingEstateTypes, TRUE) )
            return FALSE;

        return TRUE;
    }

    protected function validateTradeType($tradeType, $estates)
    {
        $existingTradeTypes = array_keys($this->_availableValuesExtractor->availableTradeTypes($estates));

        if( !in_array($tradeType, $existingTradeTypes, TRUE) )
            return FALSE;

        return TRUE;
    }

    protected function validateCurrency($currency)
    {
        $existingCurrencies = $this->_availableValuesExtractor->availableCurrencies();

        if( !in_array($currency, $existingCurrencies, TRUE) )
            return FALSE;

        return TRUE;
    }

    protected function sanitizePriceRange($priceRange, $estates, $currency)
    {
        $notValid = function($value) {
            return ( empty($value) || !is_numeric($value) || ($value < 0) );
        };

        $existingPriceRange = $this->_availableValuesExtractor->availablePriceRange($estates, $currency);

        if( !isset($priceRange['min']) || $notValid($priceRange['min']) )
            $priceRange['min'] = $existingPriceRange['min'];

        if( !isset($priceRange['max']) || $notValid($priceRange['max']) )
            $priceRange['max'] = $existingPriceRange['max'];

        return $priceRange;
    }

    protected function sanitizeSpaceRange($spaceRange, $estates)
    {
        $notValid = function($value) {
            return ( empty($value) || !is_numeric($value) || ($value < 0) );
        };

        $existingSpaceRange = $this->_availableValuesExtractor->availableSpaceRange($estates);

        if( !isset($spaceRange['min']) || $notValid($spaceRange['min']) )
            $spaceRange['min'] = $existingSpaceRange['min'];

        if( !isset($spaceRange['max']) || $notValid($spaceRange['max']) )
            $spaceRange['max'] = $existingSpaceRange['max'];

        return $spaceRange;
    }

    protected function validateFeatures($features)
    {
        $existingEstateFeatures = EstateFeatures::getEstateFeatures();

        foreach ($features as $feature => $value)
        {
            if( !in_array($feature, $existingEstateFeatures, TRUE) )
                return FALSE;

            if( !in_array($value, ['yes', 'no'], TRUE) )
                return FALSE;
        }

        return TRUE;
    }

    protected function sanitizeAttributes($attributes, $estates)
    {
        $notValid = function($value) {
            return ( empty($value) || !is_numeric($value) || ($value < 0) );
        };

        $existingEstateAttributes = $this->_availableValuesExtractor->availableAttributes($estates);

        foreach( $attributes as $attribute => $value )
        {
            if( !in_array($attribute, array_keys($existingEstateAttributes), TRUE) ) {
                unset($attributes[$attribute]);
            } else {
                if( !isset($value['min']) || $notValid($value['min']) )
                    $attributes[$attribute]['min'] = $existingEstateAttributes[$attribute]['min'];

                if( !isset($value['max']) || $notValid($value['max']) )
                    $attributes[$attribute]['max'] = $existingEstateAttributes[$attribute]['max'];

                if( $attributes[$attribute]['min'] == $existingEstateAttributes[$attribute]['min'] &&
                    $attributes[$attribute]['max'] == $existingEstateAttributes[$attribute]['max'] ) {
                    unset($attributes[$attribute]);
                }
            }
        }

        return $attributes;
    }

    protected function validateDistricts(array $districts)
    {
        $existingDistricts = array_keys(Estate::getDistricts());

        foreach($districts as $district)
        {
            if( !in_array($district, $existingDistricts, TRUE) )
                return FALSE;
        }

        return TRUE;
    }
}