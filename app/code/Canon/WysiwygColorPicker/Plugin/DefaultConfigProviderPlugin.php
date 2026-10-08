<?php
declare(strict_types=1);

namespace Canon\WysiwygColorPicker\Plugin;

use Magento\Cms\Model\Wysiwyg\DefaultConfigProvider;
use Magento\Framework\DataObject;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class DefaultConfigProviderPlugin
{
    /**
     * @var AssetRepository
     */
    private $assetRepo;

    public function __construct(AssetRepository $assetRepo)
    {
        $this->assetRepo = $assetRepo;
    }

    /**
     * Add forecolor, backcolor, fontselect/fontfamily and ToyotaType font to TinyMCE config
     *
     * @param DefaultConfigProvider $subject
     * @param DataObject $config
     * @return DataObject
     */
    public function afterGetConfig(DefaultConfigProvider $subject, DataObject $config): DataObject
    {
        $fontFormats = 'ToyotaType=ToyotaType,sans-serif; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva';

        $fontUrlRegular = $this->getFontUrl('fonts/ToyotaType-Regular.ttf');
        $fontUrlBold = $this->getFontUrl('fonts/ToyotaType-Bold.ttf');
        $fontUrlLight = $this->getFontUrl('fonts/ToyotaType-Light.ttf');
        $fontUrlSemibold = $this->getFontUrl('fonts/ToyotaType-Semibold.ttf');

        $fontCss = "
            @font-face {
                font-family: 'ToyotaType';
                src: url('{$fontUrlRegular}') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
            @font-face {
                font-family: 'ToyotaType';
                src: url('{$fontUrlBold}') format('truetype');
                font-weight: bold;
                font-style: normal;
            }
            @font-face {
                font-family: 'ToyotaType';
                src: url('{$fontUrlLight}') format('truetype');
                font-weight: 300;
                font-style: normal;
            }
            @font-face {
                font-family: 'ToyotaType';
                src: url('{$fontUrlSemibold}') format('truetype');
                font-weight: 600;
                font-style: normal;
            }
        ";

        $tinymce = (array)$config->getData('tinymce');

        if (isset($tinymce['toolbar'])) {
            if (is_string($tinymce['toolbar'])) {
                if (strpos($tinymce['toolbar'], 'forecolor') === false) {
                    $tinymce['toolbar'] = str_replace(
                        'bold italic underline',
                        'bold italic underline forecolor backcolor',
                        $tinymce['toolbar']
                    );
                    if (strpos($tinymce['toolbar'], 'forecolor') === false) {
                        $tinymce['toolbar'] .= ' | forecolor backcolor';
                    }
                }
                if (strpos($tinymce['toolbar'], 'fontselect') === false && strpos($tinymce['toolbar'], 'fontfamily') === false) {
                    $tinymce['toolbar'] = 'fontselect fontfamily ' . $tinymce['toolbar'];
                }
                if (strpos($tinymce['toolbar'], 'code') === false) {
                    $tinymce['toolbar'] .= ' | code';
                }
            }
        } else {
            $tinymce['toolbar'] = 'fontselect fontfamily blocks fontsizeinput | formatselect | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link table charmap | code';
        }

        if (isset($tinymce['plugins'])) {
            if (is_string($tinymce['plugins'])) {
                if (strpos($tinymce['plugins'], 'textcolor') === false) {
                    $tinymce['plugins'] .= ' textcolor colorpicker';
                }
                if (strpos($tinymce['plugins'], 'code') === false) {
                    $tinymce['plugins'] .= ' code';
                }
            }
        } else {
            $tinymce['plugins'] = 'textcolor colorpicker code';
        }

        $tinymce['font_formats'] = $fontFormats;
        $tinymce['fontselect_formats'] = $fontFormats;
        $tinymce['font_family_formats'] = $fontFormats;

        $existingContentStyle = $tinymce['content_style'] ?? '';
        $tinymce['content_style'] = $existingContentStyle . "\n" . $fontCss;

        $config->setData('tinymce', $tinymce);
        $config->setData('font_formats', $fontFormats);
        $config->setData('fontselect_formats', $fontFormats);

        return $config;
    }

    private function getFontUrl(string $fontPath): string
    {
        try {
            return $this->assetRepo->getUrlWithParams($fontPath, [
                'area' => 'frontend',
                'theme' => 'Canon/theme'
            ]);
        } catch (\Throwable $e) {
            return '/static/frontend/Canon/theme/en_US/' . $fontPath;
        }
    }
}

