import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

registerBlockType('sgup/astro-menu', {
    title: 'Astronomy Menu',
    icon: 'menu',
    category: 'sgup_space',

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Menu Slug"
                            value={attributes.which}
                            onChange={(value) => setAttributes({ which: value })}
                            help="Enter the menu identifier (e.g., alert-menu)"
                        />
                        <TextControl
                            label="Text Align"
                            value={attributes.text_align}
                            onChange={(value) => setAttributes({ text_align: value })}
                            help="Left or Right?"
                        />
                        <ToggleControl
                            label="Is Inline?"
                            checked={attributes.is_inline}
                            onChange={(value) => setAttributes({ is_inline: value })}
                            help="Should the menu be inline?"
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#f5f5f5', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0' }}>📋 Astronomy Menu</h3>
                        <p style={{ margin: 0, color: '#666' }}>Menu: {attributes.which}</p>
                        <p style={{ margin: 0, color: '#666' }}>Text Alignment: {attributes.text_align}</p>
                        {attributes.is_inline && <p style={{ margin: 0, color: '#666' }}>Is Inline</p>}
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});