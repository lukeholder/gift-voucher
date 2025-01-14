<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\models\VoucherTypeModel;
use verbb\giftvoucher\models\VoucherTypeSiteModel;

use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

class VoucherTypesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('giftVoucher-manageVoucherTypes');

        parent::init();
    }

    public function actionEdit(int $voucherTypeId = null, VoucherTypeModel $voucherType = null): Response
    {
        $variables = [
            'voucherTypeId' => $voucherTypeId,
            'voucherType' => $voucherType,
            'brandNewVoucherType' => false,
        ];

        if (empty($variables['voucherType'])) {
            if (!empty($variables['voucherTypeId'])) {
                $voucherTypeId = $variables['voucherTypeId'];
                $variables['voucherType'] = GiftVoucher::getInstance()->getVoucherTypes()->getVoucherTypeById($voucherTypeId);

                if (!$variables['voucherType']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['voucherType'] = new VoucherTypeModel();
                $variables['brandNewVoucherType'] = true;
            }
        }

        if (!empty($variables['voucherTypeId'])) {
            $variables['title'] = $variables['voucherType']->name;
        } else {
            $variables['title'] = Craft::t('gift-voucher', 'Create a Voucher Type');
        }

        return $this->renderTemplate('gift-voucher/voucher-types/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $voucherType = new VoucherTypeModel();

        $request = Craft::$app->getRequest();

        $voucherType->id = $request->getBodyParam('voucherTypeId');
        $voucherType->name = $request->getBodyParam('name');
        $voucherType->handle = $request->getBodyParam('handle');
        $voucherType->skuFormat = $request->getBodyParam('skuFormat');

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $request->getBodyParam('sites.' . $site->handle);

            $siteSettings = new VoucherTypeSiteModel();
            $siteSettings->siteId = $site->id;
            $siteSettings->hasUrls = !empty($postedSettings['uriFormat']);

            if ($siteSettings->hasUrls) {
                $siteSettings->uriFormat = $postedSettings['uriFormat'];
                $siteSettings->template = $postedSettings['template'];
            } else {
                $siteSettings->uriFormat = null;
                $siteSettings->template = null;
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $voucherType->setSiteSettings($allSiteSettings);

        // Set the voucher type field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Voucher::class;
        $voucherType->setFieldLayout($fieldLayout);

        // Save it
        if (GiftVoucher::getInstance()->getVoucherTypes()->saveVoucherType($voucherType)) {
            Craft::$app->getSession()->setNotice(Craft::t('gift-voucher', 'Voucher type saved.'));

            return $this->redirectToPostedUrl($voucherType);
        }

        Craft::$app->getSession()->setError(Craft::t('gift-voucher', 'Couldn’t save voucher type.'));

        // Send the voucherType back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'voucherType' => $voucherType,
        ]);

        return null;
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $voucherTypeId = Craft::$app->getRequest()->getRequiredParam('id');
        GiftVoucher::getInstance()->getVoucherTypes()->deleteVoucherTypeById($voucherTypeId);

        return $this->asJson(['success' => true]);
    }

}
