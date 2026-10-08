<?php
declare(strict_types=1);

namespace Canon\WysiwygColorPicker\Plugin;

use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\DataObject;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class WysiwygConfigPlugin
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
     * Ensure TinyMCE / CMS Wysiwyg config includes ToyotaType font formats, fontselect, forecolor and backcolor
     *
     * @param Config $subject
     * @param DataObject $config
     * @return DataObject
     */
    public function afterGetConfig(Config $subject, DataObject $config): DataObject
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

        // 1. Process tinymce data array
        $tinymce = (array)$config->getData('tinymce');
        if (!empty($tinymce)) {
            if (isset($tinymce['toolbar']) && is_string($tinymce['toolbar'])) {
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
            if (isset($tinymce['plugins']) && is_string($tinymce['plugins'])) {
                if (strpos($tinymce['plugins'], 'textcolor') === false) {
                    $tinymce['plugins'] .= ' textcolor colorpicker';
                }
                if (strpos($tinymce['plugins'], 'code') === false) {
                    $tinymce['plugins'] .= ' code';
                }
            }
            $tinymce['font_formats'] = $fontFormats;
            $tinymce['fontselect_formats'] = $fontFormats;
            $tinymce['font_family_formats'] = $fontFormats;

            $existingContentStyle = $tinymce['content_style'] ?? '';
            $tinymce['content_style'] = $existingContentStyle . "\n" . $fontCss;

            $config->setData('tinymce', $tinymce);
        }

        // 2. Process tinymce4 data array if present
        $tinymce4 = (array)$config->getData('tinymce4');
        if (!empty($tinymce4)) {
            if (isset($tinymce4['toolbar']) && is_string($tinymce4['toolbar'])) {
                if (strpos($tinymce4['toolbar'], 'forecolor') === false) {
                    $tinymce4['toolbar'] .= ' | forecolor backcolor';
                }
                if (strpos($tinymce4['toolbar'], 'fontselect') === false && strpos($tinymce4['toolbar'], 'fontfamily') === false) {
                    $tinymce4['toolbar'] = 'fontselect fontfamily ' . $tinymce4['toolbar'];
                }
                if (strpos($tinymce4['toolbar'], 'code') === false) {
                    $tinymce4['toolbar'] .= ' | code';
                }
            }
            if (isset($tinymce4['plugins']) && is_array($tinymce4['plugins'])) {
                if (!in_array('textcolor', $tinymce4['plugins'])) {
                    $tinymce4['plugins'][] = 'textcolor';
                }
                if (!in_array('colorpicker', $tinymce4['plugins'])) {
                    $tinymce4['plugins'][] = 'colorpicker';
                }
                if (!in_array('code', $tinymce4['plugins'])) {
                    $tinymce4['plugins'][] = 'code';
                }
            }
            $tinymce4['font_formats'] = $fontFormats;
            $tinymce4['fontselect_formats'] = $fontFormats;

            $existingContentStyle = $tinymce4['content_style'] ?? '';
            $tinymce4['content_style'] = $existingContentStyle . "\n" . $fontCss;

            $config->setData('tinymce4', $tinymce4);
        }

        // 3. Process top-level settings array if present
        $settings = (array)$config->getData('settings');
        if (!empty($settings)) {
            if (isset($settings['toolbar']) && is_string($settings['toolbar'])) {
                if (strpos($settings['toolbar'], 'forecolor') === false) {
                    $settings['toolbar'] .= ' | forecolor backcolor';
                }
                if (strpos($settings['toolbar'], 'fontselect') === false && strpos($settings['toolbar'], 'fontfamily') === false) {
                    $settings['toolbar'] = 'fontselect fontfamily ' . $settings['toolbar'];
                }
                if (strpos($settings['toolbar'], 'code') === false) {
                    $settings['toolbar'] .= ' | code';
                }
            }
            if (isset($settings['plugins']) && is_string($settings['plugins'])) {
                if (strpos($settings['plugins'], 'textcolor') === false) {
                    $settings['plugins'] .= ' textcolor colorpicker';
                }
                if (strpos($settings['plugins'], 'code') === false) {
                    $settings['plugins'] .= ' code';
                }
            }
            $settings['font_formats'] = $fontFormats;
            $settings['fontselect_formats'] = $fontFormats;

            $existingContentStyle = $settings['content_style'] ?? '';
            $settings['content_style'] = $existingContentStyle . "\n" . $fontCss;

            $config->setData('settings', $settings);
        }

        // Top level settings on DataObject
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

