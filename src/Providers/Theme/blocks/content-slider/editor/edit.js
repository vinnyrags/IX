import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    SelectControl,
    __experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes }) {
    const { showArrows, autoplay, autoplayInterval, centerContent, transition } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Slider Settings', 'ix')} initialOpen={true}>
                    <SelectControl
                        label={__('Transition', 'ix')}
                        value={transition || 'slide'}
                        options={[
                            { label: __('Slide', 'ix'), value: 'slide' },
                            { label: __('Fade', 'ix'), value: 'fade' },
                        ]}
                        onChange={(value) => setAttributes({ transition: value })}
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                    <ToggleControl
                        label={__('Show arrows', 'ix')}
                        checked={!!showArrows}
                        onChange={(value) => setAttributes({ showArrows: value })}
                        __nextHasNoMarginBottom
                    />
                    <ToggleControl
                        label={__('Autoplay', 'ix')}
                        checked={!!autoplay}
                        onChange={(value) => setAttributes({ autoplay: value })}
                        __nextHasNoMarginBottom
                    />
                    {autoplay && NumberControl && (
                        <NumberControl
                            label={__('Autoplay interval (seconds)', 'ix')}
                            value={autoplayInterval}
                            onChange={(value) => {
                                const next = parseFloat(value);
                                setAttributes({
                                    autoplayInterval: Number.isFinite(next) && next > 0 ? next : 5,
                                });
                            }}
                            min={1}
                            max={60}
                            step={1}
                            __next40pxDefaultSize
                        />
                    )}
                    <ToggleControl
                        label={__('Center content vertically', 'ix')}
                        help={__('Useful when slides have varying content heights — shorter slides center inside the tallest slide instead of sitting at the top.', 'ix')}
                        checked={!!centerContent}
                        onChange={(value) => setAttributes({ centerContent: value })}
                        __nextHasNoMarginBottom
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <InnerBlocks
                    renderAppender={() => <InnerBlocks.ButtonBlockAppender />}
                />
            </div>
        </>
    );
}
