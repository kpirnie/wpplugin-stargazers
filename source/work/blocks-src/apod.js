import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, TextControl, Placeholder, Spinner } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState } from '@wordpress/element';

registerBlockType('sgup/apod', {
    title: 'Astronomy Picture of the Day',
    icon: 'format-image',
    category: 'sgup_space',

    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        const [hasError, setHasError] = useState(false);

        // Fallback placeholder content
        const FallbackPreview = () => (
            <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#f5f5f5', borderRadius: '4px' }}>
                <h3 style={{ margin: '0 0 10px 0' }}>🖼️ {attributes.title}</h3>
                <p style={{ margin: 0, color: '#666' }}>APOD will display here on the front-end.</p>
                {hasError && (
                    <p style={{ margin: '10px 0 0 0', color: '#d63638', fontSize: '12px' }}>
                        ⚠️ Preview unavailable - block will render on front-end
                    </p>
                )}
            </div>
        );

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Title"
                            value={attributes.title}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    {!hasError ? (
                        <ServerSideRender
                            block="sgup/apod"
                            attributes={attributes}
                            EmptyResponsePlaceholder={FallbackPreview}
                            ErrorPlaceholder={({ error }) => {
                                setHasError(true);
                                return <FallbackPreview />;
                            }}
                            LoadingResponsePlaceholder={() => (
                                <Placeholder>
                                    <Spinner />
                                    <p>Loading preview...</p>
                                </Placeholder>
                            )}
                        />
                    ) : (
                        <FallbackPreview />
                    )}
                </div>
            </>
        );
    },

    save: () => null
});
