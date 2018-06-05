<?php

namespace spec\Pim\Component\Catalog\EntityWithFamily;

use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\ChannelInterface;
use Pim\Component\Catalog\Model\LocaleInterface;

class RequiredValueSpec extends ObjectBehavior
{
    function let(
        AttributeInterface $attribute,
        ChannelInterface $channel,
        LocaleInterface $locale
    ) {
        $attribute->getCode()->willReturn('attribute_code');
        $channel->getCode()->willReturn('channel');
        $locale->getCode()->willReturn('locale');
        $this->beConstructedWith($attribute, $channel, $locale);
    }

    function it_returns_the_attribute($attribute)
    {
        $this->forAttribute()->shouldReturn($attribute);
    }

    function it_returns_the_scope($channel)
    {
        $this->forScope()->shouldReturn($channel);
    }

    function it_returns_the_locale($locale)
    {
        $this->forLocale()->shouldReturn($locale);
    }

    function it_helps_to_retrieve_the_value_when_the_attribute_is_non_scopable_non_localizable_non_locale_specific(
        $attribute,
        $locale
    ) {
        $attribute->isScopable()->willReturn(false);
        $attribute->isLocalizable()->willReturn(false);
        $attribute->isLocaleSpecific()->willReturn(false);

        $this->attribute()->shouldReturn('attribute_code');
        $this->scope()->shouldReturn(null);
        $this->locale($locale)->shouldReturn(null);
    }

    function it_helps_to_retrive_the_when_the_attribute_is_scopable_non_localizable_non_locale_specific(
        $attribute,
        $locale
    ) {
        $attribute->getCode()->willReturn('attribute_code');
        $attribute->isScopable()->willReturn(true);
        $attribute->isLocalizable()->willReturn(false);
        $attribute->isLocaleSpecific()->willReturn(false);

        $this->attribute()->shouldReturn('attribute_code');
        $this->scope()->shouldReturn('channel');
        $this->locale($locale)->shouldReturn(null);
    }

    function it_helps_to_retrive_the_when_the_attribute_is_localizable_non_scopable_non_locale_specific(
        $attribute,
        $locale
    ) {
        $attribute->getCode()->willReturn('attribute_code');
        $attribute->isScopable()->willReturn(false);
        $attribute->isLocalizable()->willReturn(true);
        $attribute->isLocaleSpecific()->willReturn(false);

        $this->attribute()->shouldReturn('attribute_code');
        $this->scope()->shouldReturn(null);
        $this->locale($locale)->shouldReturn('locale');
    }

    function it_helps_to_retrive_the_when_the_locale_specific_non_scopable_non_localizable(
        $attribute,
        $locale,
        LocaleInterface $anotherLocale
    ) {
        $attribute->getCode()->willReturn('attribute_code');
        $attribute->isScopable()->willReturn(false);
        $attribute->isLocalizable()->willReturn(false);
        $attribute->isLocaleSpecific()->willReturn(true);
        $attribute->hasLocaleSpecific($locale)->willReturn(true);
        $attribute->hasLocaleSpecific($anotherLocale)->willReturn(false);

        $this->attribute()->shouldReturn('attribute_code');
        $this->scope()->shouldReturn(null);
        $this->locale($locale)->shouldReturn(null);
        $this->locale($anotherLocale)->shouldReturn(null);
    }
}
