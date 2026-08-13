import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, RangeControl, TextControl, ToggleControl } from '@wordpress/components';

registerBlockType('sgup/light-pollution', {
    title: 'Light Pollution Map',
    icon: 'visibility',
    category: 'sgup_space',

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Title"
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                        <ToggleControl
                            label="Show Title"
                            checked={attributes.showTitle}
                            onChange={(value) => setAttributes({ showTitle: value })}
                        />
                        <ToggleControl
                            label="Show Location Picker"
                            checked={attributes.showLocationPicker}
                            onChange={(value) => setAttributes({ showLocationPicker: value })}
                        />
                        <RangeControl
                            label="Max Height (px)"
                            value={attributes.maxHeight}
                            onChange={(value) => setAttributes({ maxHeight: value })}
                            min={200}
                            max={800}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#1a1a2e', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0', color: '#fff' }}>
                            🌃 {attributes.title} <small style={{ color: '#888' }}>({attributes.showTitle ? 'Shown' : 'Hidden'})</small>
                        </h3>
                        <p style={{ margin: 0, color: '#aaa' }}>Light pollution map will display here.</p>
                        <p style={{ margin: '5px 0', fontSize: '12px', color: '#666' }}>
                            <em>Height: {attributes.maxHeight}px</em>
                        </p>
                        {attributes.showLocationPicker && (
                            <p style={{ margin: '5px 0', fontSize: '12px', color: '#666' }}>
                                <em>Location picker enabled</em>
                            </p>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});
