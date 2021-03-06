<?php
// src/AppBundle/Admin/EstateAdmin.php
namespace AppBundle\Admin;

use Symfony\Component\Validator\Constraints as Assert;

use Sonata\AdminBundle\Admin\Admin,
    Sonata\AdminBundle\Datagrid\ListMapper,
    Sonata\AdminBundle\Datagrid\DatagridMapper,
    Sonata\AdminBundle\Form\FormMapper;

use Gedmo\Sluggable\Util as Sluggable;

use AppBundle\Entity\Estate,
    AppBundle\Service\GeoCoder;

class EstateAdmin extends Admin
{
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier("code", "text", [
                "label" => "ID"
            ])
            ->add("title", "text", [
                "label" => "Назва об'єкту"
            ])
            ->add("address", "text", [
                "label" => "Адреса об'єкту"
            ])
            ->add("estateType", NULL, [
                "label" => "Тип нерухомості"
            ])
            ->add("priceUAH", "number", [
                "label"       => "Ціна (UAH)",
                "precision"   => 2
            ])
            ->add("pricePerSquareUAH", "number", [
                "label"       => "Ціна за м2 (UAH)",
                "precision"   => 2
            ])
            ->add('isActive', 'boolean', [
                "label" => "Відображається"
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $translator = $this->getConfigurationPool()->getContainer()->get("translator");

        $lTranslator = function($input, $transDomain) use ($translator)
        {
            $output = [];

            foreach($input as $key => $value) {
                $output[$key] = $translator->trans($value, [], $transDomain);
            }

            return $output;
        };

        $tradeTypes = $lTranslator(Estate::getTradeTypes(), "trade_types");
        $districts  = $lTranslator(Estate::getDistricts(), "districts");

        $estateTypeQuery = $this->modelManager
            ->getEntityManager('AppBundle\Entity\EstateType')
            ->createQueryBuilder()
            ->select("estateType")
            ->from('AppBundle\Entity\EstateType', 'estateType')
            ->where("estateType.parent IS NOT NULL")
        ;

        $formMapper
            ->with("Нерухомість - Локалізований контент")
                ->add("translations", "a2lix_translations_gedmo", [
                    "locales"            => ['ua', 'en'],
                    "label"              => FALSE,
                    "translatable_class" => 'AppBundle\Entity\Estate',
                    "required"           => TRUE,
                    "fields"             => [
                        "title" => [
                            "locale_options" => [
                                "ua" => [
                                    "required" => FALSE,
                                    "label"    => "Назва об'єкту"
                                ],
                                "en" => [
                                    "required" => FALSE,
                                    "label"    => "Estate title"
                                ]
                            ]
                        ],
                        "address" => [
                            "locale_options" => [
                                "ua" => [
                                    "label" => "Точна адреса об'єкту"
                                ],
                                "en" => [
                                    "required" => FALSE,
                                    "label"    => "Exact property address"
                                ]
                            ]
                        ],
                        "description" => [
                            "locale_options" => [
                                'ua' => [
                                    'label'       => 'Опис',
                                    'field_type'  => 'ckeditor',
                                    'config_name' => 'base_config'
                                ],
                                'en' => [
                                    "required"    => FALSE,
                                    'label'       => 'Description',
                                    'field_type'  => 'ckeditor',
                                    'config_name' => 'base_config'
                                ]
                            ]
                        ],
                        'slug' => [
                            'display' => FALSE
                        ]
                    ]
                ])
            ->end()
            ->with("Нерухомість - Ручне введення координат")
                ->add("coordinatesManualLat", "text", [
                    'required' => FALSE,
                    'label'    => "Широта"
                ])
                ->add("coordinatesManualLng", "text", [
                    'required' => FALSE,
                    'label'    => "Довгота"
                ])
            ->end()
            ->with("Нерухомість - Загальні дані")
                ->add("code", "text", [
                    "label" => "ID"
                ])
                ->add("tradeType", "choice", [
                    "label"       => "Тип транзакції",
                    "placeholder" => "Оберіть тип транзакції...",
                    "choices"     => $tradeTypes
                ])
                ->add("estateType", NULL, [
                    "label"         => "Тип нерухомості",
                    "required"      => TRUE,
                    "placeholder"   => "Оберіть тип нерухомості...",
                    "class"         => 'AppBundle\Entity\EstateType',
                    "query_builder" => $estateTypeQuery
                ], [
                    'edit' => 'standard'
                ])
                ->add("district", "choice", [
                    "label"       => "Район",
                    "placeholder" => "Оберіть район...",
                    "choices"     => $districts
                ])
                ->add("space", "number", [
                    "label"     => "Площа",
                    "precision" => 2
                ])
                ->add("spacePlot", "number", [
                    "required"  => FALSE,
                    "label"     => "Площа ділянки",
                    "precision" => 2
                ])
                ->add("priceUAH", "number", [
                    "required"    => FALSE,
                    "label"       => "Ціна (UAH)",
                    "precision"   => 2,
                    "constraints" => [
                        new Assert\Range(["min" => 0])
                    ]
                ])
                ->add("priceUSD", "number", [
                    "label"       => "Ціна (USD)",
                    "precision"   => 2,
                    "constraints" => [
                        new Assert\Range(["min" => 0])
                    ]
                ])
                ->add("pricePerSquareUAH", "number", [
                    "required"    => FALSE,
                    "label"       => "Ціна за м2 (UAH)",
                    "precision"   => 2,
                    "constraints" => [
                        new Assert\Range(["min" => 0])
                    ]
                ])
                ->add("pricePerSquareUSD", "number", [
                    "required"    => FALSE,
                    "label"       => "Ціна за м2 (USD)",
                    "precision"   => 2,
                    "constraints" => [
                        new Assert\Range(["min" => 0])
                    ]
                ])
                ->add('isActive', 'checkbox', [
                    "required" => FALSE,
                    "label"    => "Відображається"
                ])
            ->end()
            ->with("Нерухомість - Особливості")
                ->add('estateFeatures', 'sonata_type_admin', [
                    'label'  => FALSE,
                    'delete' => FALSE
                ])
            ->end()
            ->with("Нерухомість - Характеристики")
                ->add('estateAttribute', 'sonata_type_collection', [
                    'label'        => FALSE,
                    'by_reference' => FALSE,
                    'btn_add'      => "Додати характеристику"
                ], [
                    'edit'   => 'inline',
                    'inline' => 'table'
                ])
            ->end()
            ->with("Нерухомість - Фотографії")
                ->add('estatePhoto', 'sonata_type_collection', [
                    'required'     => TRUE,
                    'label'        => "Керування зображеннями",
                    'by_reference' => FALSE,
                    'btn_add'      => "Додати зображення"
                ], [
                    'edit'     => 'inline',
                    'inline'   => 'table',
                    'sortable' => 'position'
                ])
            ->end()
        ;
    }

    public function prePersist($estate)
    {
        if( !($estate instanceof Estate) )
            return;

        $this->setCoordinates($estate);
        $this->convertPrices($estate);
    }

    public function postPersist($estate)
    {
        if( !($estate instanceof Estate) )
            return;

        $this->setSlug($estate);
    }

    public function preUpdate($estate)
    {
        if( !($estate instanceof Estate) )
            return;

        $this->setCoordinates($estate);
        $this->convertPrices($estate);
    }

    public function postUpdate($estate)
    {
        if( !($estate instanceof Estate) )
            return;

        $this->setSlug($estate);
    }

    protected function setSlug($estate)
    {
        $originalAddress = $estate->getAddress();

        $estate->setTranslatableLocale('en');

        $_manager = $this->getConfigurationPool()->getContainer()->get('Doctrine')->getManager();
        $_manager->refresh($estate);

        $slug = ( $estate->getAddress() )
            ? $estate->getAddress()
            : $originalAddress
        ;

        $slug = Sluggable\Urlizer::transliterate($slug, '_');

        $estate->setSlug(Sluggable\Urlizer::urlize($slug, '_'));

        $_manager->persist($estate);
        $_manager->flush();
    }

    protected function setCoordinates($estate)
    {
        $geoCoder = $this->getConfigurationPool()->getContainer()->get('app.geo_coder');

        if( $estate->getCoordinatesManualLat() && $estate->getCoordinatesManualLng() ) {
            $coordinates = $geoCoder->getCoordinatesManual(
                $estate->getCoordinatesManualLat(),
                $estate->getCoordinatesManualLng()
            );
        } else {
            $coordinates = $geoCoder->getCoordinates(
                $estate->getAddress()
            );
        }

        if( $coordinates )
            $estate->setCoordinates($coordinates);
    }

    protected function convertPrices($estate)
    {
        $currencyConverter = $this->getConfigurationPool()->getContainer()->get('app.currency_converter');

        if( $estate->getPriceUSD() && empty($estate->getPriceUAH()) )
        {
            if( ($priceUAH = $currencyConverter->USD_UAH()->convert($estate->getPriceUSD())) )
            {
                $estate->setPriceUAH($priceUAH);
            }
        }

        if( $estate->getPricePerSquareUSD() && empty($estate->getPricePerSquareUAH()) )
        {
            if( ($priceUAH = $currencyConverter->USD_UAH()->convert($estate->getPricePerSquareUSD())) )
            {
                $estate->setPricePerSquareUAH($priceUAH);
            }
        }
    }

    public function getFormTheme()
    {
        return array_merge(
            parent::getFormTheme(),
            array('ApplicationSonataUserBundle:Admin/Form:form_admin_fields.html.twig')
        );
    }
}
