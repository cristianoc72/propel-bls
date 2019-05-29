<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Domain;

/**
 * Trait DomainPart
 *
 * @author Cristiano Cinotti
 */
trait DomainPart
{
    /**
     * @var Domain
     */
    private $domain;

    /**
     * @param Domain $domain
     *
     * @return void
     */
    public function setDomain(Domain $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return Domain
     */
    public function getDomain(): Domain
    {
        return $this->domain;
    }
}
