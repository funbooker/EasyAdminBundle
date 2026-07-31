<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\ProjectDomain;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain\ProjectReleaseCategory;

/**
 * @extends AbstractCrudController<ProjectReleaseCategory>
 */
class ProjectReleaseCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectReleaseCategory::class;
    }
}
