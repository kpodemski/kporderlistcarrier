<?php

use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class KpOrderListCarrier extends Module
{
    /** @var array */
    public const MODULE_HOOKS = [
        'actionOrderGridDefinitionModifier',
        'actionOrderGridQueryBuilderModifier',
    ];

    /** @var string */
    public const CARRIER_FIELD_NAME = 'carrier_name';

    public function __construct()
    {
        $this->name = 'kporderlistcarrier';
        $this->author = 'Krystian Podemski';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Carrier on the orders list');
        $this->description = $this->l('Displays the name of the carrier on your orders list in back office');
    }

    /**
     * Installer.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook(static::MODULE_HOOKS);
    }

    /**
     * Modifies Order list Grid.
     *
     * @return void
     */
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface $definition */
        $definition = $params['definition'];

        $definition
            ->getColumns()
            ->addAfter(
                'payment',
                (new DataColumn(static::CARRIER_FIELD_NAME))
                    ->setName($this->l('Carrier name'))
                    ->setOptions([
                        'field' => static::CARRIER_FIELD_NAME,
                ])
            )
        ;

        $filters = $definition->getFilters();
        $filters->add((new Filter(static::CARRIER_FIELD_NAME, TextType::class))
            ->setTypeOptions([
                'required' => false,
            ])
            ->setAssociatedColumn(static::CARRIER_FIELD_NAME)
        );
    }

    /**
     * Handle order list queries and filters.
     *
     * @return void
     */
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $params['search_query_builder'];

        /** @var \PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface */
        $searchCriteria = $params['search_criteria'];

        $queryBuilder->addSelect(
            'IF(carrier.name = "0", "'.Configuration::get('PS_SHOP_NAME').'", carrier.name) carrier_name'
        );

        $queryBuilder->leftJoin('o', _DB_PREFIX_.'carrier', 'carrier', 'o.id_carrier = carrier.id_carrier');

        if (static::CARRIER_FIELD_NAME === $searchCriteria->getOrderBy()) {
            $queryBuilder->orderBy(static::CARRIER_FIELD_NAME, $searchCriteria->getOrderWay());
        }

        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if (static::CARRIER_FIELD_NAME === $filterName) {
                $queryBuilder->having(static::CARRIER_FIELD_NAME.' LIKE :'.static::CARRIER_FIELD_NAME);
                $queryBuilder->setParameter(static::CARRIER_FIELD_NAME, '%'.$filterValue.'%');
            }
        }
    }
}
