<?php
// src/AppBundle/Validator/Constraints/IsTypeCommercialValidConstraintValidator.php
namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint,
    Symfony\Component\Validator\ConstraintValidator;

use Doctrine\ORM\EntityManager;

class IsTypeCommercialValidConstraintValidator extends ConstraintValidator
{
    protected $_entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->_entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        $validStringIds = [];

        $validTypes = $this->_entityManager->getRepository("AppBundle:EstateType")->findSpecificType(2);

        foreach($validTypes as $validType) {
            $validStringIds[] = $validType['stringId'];
        }

        if( !in_array($value, $validStringIds) ) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
