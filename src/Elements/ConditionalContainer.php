<?php

namespace Itsbessner\ConditionalContainer\Elements;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


#[AsContentElement('itsbessner_conditional_container_element', category: 'ITS Bessner', nestedFragments: true)]
class ConditionalContainer extends AbstractContentElementController
{

    const OPTION_VALENTINE = 1;
    const OPTION_EASTER = 2;
    const OPTION_XMAS = 3;
    const OPTION_MOTHERSDAY = 4;

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {

        return $this->render("@ItsbessnerConditionalContainer/ce/conditional_container.html.twig", [
            "pid" => $model->id,
            "fragments" => ContentModel::findBy(["pid = ?", "ptable = ?"], [$model->id, 'tl_content']),
            "isBackend" => self::isBackend($request),
            "options" => self::getOptions(),
            "fulfilled" => self::isConditionFulfilled(null, $model->itsbessner_conditional_days_before),
            "options_selected" => unserialize($model->itsbessner_conditional_selection),
            "daysBefore" => $model->itsbessner_conditional_days_before,
            "isValid" => self::isValid($model->itsbessner_conditional_days_before, unserialize($model->itsbessner_conditional_selection))
        ]);
    }

    public static function getOptions(): array
    {
        return [
            self::OPTION_VALENTINE => "Valentinstag",
            self::OPTION_EASTER => "Ostern",
            self::OPTION_XMAS => "Weihnachten",
            self::OPTION_MOTHERSDAY => "Muttertag"
        ];
    }

    public static function isBackend($request): bool
    {
        return $request->get('_scope') == 'backend';
    }

    public static function isValid($daysBefore, $optionsSelected): bool
    {
        foreach ($optionsSelected as $option) {
            if (self::isConditionFulfilled($option, $daysBefore)) {
                return true;
            }
        }
        return false;
    }


    public static function isConditionFulfilled($condition, $daysBefore): bool|array
    {

        if ($condition == self::OPTION_VALENTINE) {
            $date = new DateTime("February 14th");
            $dateTo = $date->getTimestamp();
            $date->modify("-$daysBefore days");
            $dateFrom = $date->getTimestamp();
            return $dateFrom <= time() && time() <= $dateTo;
        }
        if ($condition == self::OPTION_EASTER) {
            $date = new DateTime();
            $date->setTimestamp(easter_date());
            $date->modify("+2 days");
            $dateTo = $date->getTimestamp();
            $date->modify("-$daysBefore days");
            $date->modify("-2 days");
            $dateFrom = $date->getTimestamp();
            return $dateFrom <= time() && time() <= $dateTo;
        }
        if ($condition == self::OPTION_XMAS) {
            $date = new DateTime("december 27th");
            $dateTo = $date->getTimestamp();
            $date->modify("-2 days");
            $date->modify("-$daysBefore days");
            $dateFrom = $date->getTimestamp();
            return $dateFrom <= time() && time() <= $dateTo;
        }
        if ($condition == self::OPTION_MOTHERSDAY) {
            $date = new DateTime("second sunday of may");
            $dateTo = $date->getTimestamp();
            $date->modify("-$daysBefore days");
            $dateFrom = $date->getTimestamp();
            return $dateFrom <= time() && time() <= $dateTo;
        }

        return [
            self::OPTION_VALENTINE => self::isConditionFulfilled(self::OPTION_VALENTINE, $daysBefore),
            self::OPTION_EASTER => self::isConditionFulfilled(self::OPTION_EASTER, $daysBefore),
            self::OPTION_XMAS => self::isConditionFulfilled(self::OPTION_XMAS, $daysBefore),
            self::OPTION_MOTHERSDAY => self::isConditionFulfilled(self::OPTION_MOTHERSDAY, $daysBefore),
        ];


    }


}