<?php

namespace Pim\Bundle\EnrichBundle\Normalizer;

use Akeneo\Component\Localization\Presenter\PresenterInterface;
use Akeneo\Component\Versioning\Model\Version;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Pim\Component\Catalog\Localization\Presenter\PresenterRegistryInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Version normalizer
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class VersionNormalizer implements NormalizerInterface
{
    /** @var UserManager */
    protected $userManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PresenterRegistryInterface */
    protected $presenterRegistry;

    /** @var string[] */
    protected $supportedFormats = ['internal_api'];

    /** @var array */
    protected $authorCache = [];

    /** @var PresenterInterface */
    protected $datetimePresenter;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    const ATTRIBUTE_HEADER_SEPARATOR = "-";

    /**
     * @param UserManager                  $userManager
     * @param TranslatorInterface          $translator
     * @param PresenterInterface           $datetimePresenter
     * @param PresenterRegistryInterface   $presenterRegistry
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        UserManager $userManager,
        TranslatorInterface $translator,
        PresenterInterface $datetimePresenter,
        PresenterRegistryInterface $presenterRegistry,
        AttributeRepositoryInterface $attributeRepository = null // TODO on master: remove = null
    ) {
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->datetimePresenter = $datetimePresenter;
        $this->presenterRegistry = $presenterRegistry;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($version, $format = null, array $context = [])
    {
        $context = ['locale' => $this->translator->getLocale()];

        return [
            'id'           => $version->getId(),
            'author'       => $this->normalizeAuthor($version->getAuthor()),
            'resource_id'  => (string) $version->getResourceId(),
            'snapshot'     => $version->getSnapshot(),
            'changeset'    => $this->convertChangeset($version->getChangeset(), $context),
            'context'      => $version->getContext(),
            'version'      => $version->getVersion(),
            'logged_at'    => $this->datetimePresenter->present($version->getLoggedAt(), $context),
            'pending'      => $version->isPending()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Version && in_array($format, $this->supportedFormats);
    }

    /**
     * @param string $author
     *
     * @return string
     */
    protected function normalizeAuthor($author)
    {
        if (!isset($this->authorCache[$author])) {
            $user = $this->userManager->findUserByUsername($author);

            if (null === $user) {
                $userName = sprintf('%s - %s', $author, $this->translator->trans('Removed user'));
            } else {
                $userName = sprintf('%s %s - %s', $user->getFirstName(), $user->getLastName(), $user->getEmail());
            }

            $this->authorCache[$author] = $userName;
        }

        return $this->authorCache[$author];
    }

    /**
     * Localize the changeset values
     *
     * @param array $changeset
     * @param array $context
     *
     * @return array
     */
    protected function convertChangeset(array $changeset, array $context)
    {
        // TODO on master: remove this check and old behavior
        if (null === $this->attributeRepository) {
            // TODO on master: remove this behavior (previous behavior kept to avoid BC break)
            foreach ($changeset as $attribute => $changes) {
                $context['versioned_attribute'] = $attribute;
                $attributeName = $attribute;
                if (preg_match('/^(?<attribute>[a-zA-Z0-9_]+)-.+$/', $attribute, $matches)) {
                    $attributeName = $matches['attribute'];
                }
                $presenter = $this->presenterRegistry->getPresenterByAttributeCode($attributeName);
                if (null !== $presenter) {
                    foreach ($changes as $key => $value) {
                        $changeset[$attribute][$key] = $presenter->present($value, $context);
                    }
                }
            }

            return $changeset;
        } else {
            // TODO on master: keep only this behavior
            $attributeCodes = [];
            foreach (array_keys($changeset) as $valueHeader) {
                $attributeCode = $this->extractAttributeCode($valueHeader);

                $attributeCodes[$attributeCode] = true;
            }

            $attributeTypes = $this->attributeRepository->getAttributeTypeByCodes(array_keys($attributeCodes));

            foreach ($changeset as $valueHeader => $valueChanges) {
                $context['versioned_attribute'] = $valueHeader;
                $attributeCode = $this->extractAttributeCode($valueHeader);

                if (isset($attributeTypes[$attributeCode])) {
                    $presenter = $this->presenterRegistry->getPresenterByAttributeType($attributeTypes[$attributeCode]);
                    if (null !== $presenter) {
                        foreach ($valueChanges as $key => $value) {
                            $changeset[$valueHeader][$key] = $presenter->present($value, $context);
                        }
                    }
                }
            }

            return $changeset;
        }
    }

    /**
     * Extract the attribute code from the versioning value header.
     * For example, in "price-EUR", the attribute code is "price".
     * For "desc-ecom-en_US", this is "desc".
     */
    protected function extractAttributeCode($valueHeader)
    {
        if (($separatorPos = strpos($valueHeader, self::ATTRIBUTE_HEADER_SEPARATOR)) !== false) {
            return substr($valueHeader, 0, $separatorPos);
        } else {
            return $valueHeader;
        }
    }
}
