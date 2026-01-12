<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NestedFilter;

/**
 * @author Brandon Marcachi <brandon.marcachi@gmail.com>
 */
final class NestedConfigurator implements FilterConfiguratorInterface
{

    /**
     * @param iterable<FilterConfiguratorInterface> $filterConfigurators
     */
    public function __construct(private ManagerRegistry $doctrine, private iterable $filterConfigurators = [])
    {
    }

    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContextInterface $context): bool
    {
        return NestedFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContextInterface $context): void
    {
        $entityFqcn = $entityDto->getFqcn();

        [$targetClassMetadata, $targetProperty] = NestedFilter::extractTargets(
            $this->getObjectManager($entityFqcn),
            $entityFqcn,
            $filterDto->getProperty()
        );

        $wrappedEntityDto = new EntityDto($targetClassMetadata->getName(), $targetClassMetadata);
        $wrappedFilter = $this->extractWrappedFilter($filterDto);
        $wrappedFilterDto = $wrappedFilter->getAsDto();
        $wrappedFilterDto->setProperty($targetProperty);

        $this->configureFilter($wrappedFilterDto, $wrappedEntityDto, $context);

        $filterDto->setFormType($wrappedFilterDto->getFormType());
        $filterDto->setFormTypeOptions($wrappedFilterDto->getFormTypeOptions());

        $this->removeWrappedFilterOption($filterDto);
    }

    private function extractWrappedFilter(FilterDto $filterDto): FilterInterface
    {
        return $filterDto->getFormTypeOption(NestedFilter::FORM_OPTION_WRAPPED_FILTER);
    }

    private function removeWrappedFilterOption(FilterDto $filterDto): void
    {
        [$root, $filterKey] = explode('.', NestedFilter::FORM_OPTION_WRAPPED_FILTER);

        $data = $filterDto->getFormTypeOption($root);
        unset($data[$filterKey]);
        $filterDto->setFormTypeOption($root, $data);
    }

    private function configureFilter(FilterDto $filterDto, EntityDto $entityDto, AdminContextInterface $context): void
    {
        foreach ($this->filterConfigurators as $configurator) {
            if ($configurator->supports($filterDto, null, $entityDto, $context)) {
                $configurator->configure($filterDto, null, $entityDto, $context);
            }
        }
    }

    private function getObjectManager(string $entityFqcn): ObjectManager
    {
        if (null === $objectManager = $this->doctrine->getManagerForClass($entityFqcn)) {
            throw new \RuntimeException(sprintf('There is no Doctrine Object Manager defined for the "%s" class.', $entityFqcn));
        }

        return $objectManager;
    }
}
