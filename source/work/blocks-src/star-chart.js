import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

registerBlockType('sgup/star-chart', {
    title: 'Star Chart',
    icon: 'star-filled',
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
                    </PanelBody>
                    <PanelBody title="Chart Options" initialOpen={false}>
                        <SelectControl
                            label="Projection"
                            value={attributes.projection}
                            options={[
                                { label: 'Stereographic', value: 'stereo' },
                                { label: 'Polar', value: 'polar' },
                                { label: 'Lambert', value: 'lambert' },
                                { label: 'Equirectangular', value: 'equirectangular' },
                                { label: 'Mollweide', value: 'mollweide' },
                                { label: 'Planechart', value: 'planechart' },
                            ]}
                            onChange={(value) => setAttributes({ projection: value })}
                        />
                        <SelectControl
                            label="Style"
                            value={attributes.style}
                            options={[
                                { label: 'Default', value: 'default' },
                                { label: 'Inverted', value: 'inverted' },
                                { label: 'Navy', value: 'navy' },
                                { label: 'Red', value: 'red' },
                            ]}
                            onChange={(value) => setAttributes({ style: value })}
                        />
                        <RangeControl
                            label="Zoom"
                            value={attributes.zoom}
                            onChange={(value) => setAttributes({ zoom: value })}
                            min={1}
                            max={6}
                        />
                        <RangeControl
                            label="Azimuth"
                            value={attributes.az}
                            onChange={(value) => setAttributes({ az: value })}
                            min={0}
                            max={360}
                        />
                    </PanelBody>
                    <PanelBody title="Display Options" initialOpen={false}>
                        <ToggleControl
                            label="Show Stars"
                            checked={attributes.showStars}
                            onChange={(value) => setAttributes({ showStars: value })}
                        />
                        <ToggleControl
                            label="Show Star Labels"
                            checked={attributes.showStarLabels}
                            onChange={(value) => setAttributes({ showStarLabels: value })}
                        />
                        <ToggleControl
                            label="Show Constellations"
                            checked={attributes.showConstellations}
                            onChange={(value) => setAttributes({ showConstellations: value })}
                        />
                        <ToggleControl
                            label="Show Constellation Labels"
                            checked={attributes.showConstellationLabels}
                            onChange={(value) => setAttributes({ showConstellationLabels: value })}
                        />
                        <ToggleControl
                            label="Show Planets"
                            checked={attributes.showPlanets}
                            onChange={(value) => setAttributes({ showPlanets: value })}
                        />
                        <ToggleControl
                            label="Show Planet Labels"
                            checked={attributes.showPlanetLabels}
                            onChange={(value) => setAttributes({ showPlanetLabels: value })}
                        />
                        <ToggleControl
                            label="Show Orbits"
                            checked={attributes.showOrbits}
                            onChange={(value) => setAttributes({ showOrbits: value })}
                        />
                        <ToggleControl
                            label="Show Galaxy"
                            checked={attributes.showGalaxy}
                            onChange={(value) => setAttributes({ showGalaxy: value })}
                        />
                        <ToggleControl
                            label="Show Ground"
                            checked={attributes.ground}
                            onChange={(value) => setAttributes({ ground: value })}
                        />
                        <ToggleControl
                            label="Gradient"
                            checked={attributes.gradient}
                            onChange={(value) => setAttributes({ gradient: value })}
                        />
                    </PanelBody>
                    <PanelBody title="Controls" initialOpen={false}>
                        <ToggleControl
                            label="Keyboard Controls"
                            checked={attributes.keyboard}
                            onChange={(value) => setAttributes({ keyboard: value })}
                        />
                        <ToggleControl
                            label="Mouse Controls"
                            checked={attributes.mouse}
                            onChange={(value) => setAttributes({ mouse: value })}
                        />
                        <ToggleControl
                            label="Live Update"
                            checked={attributes.live}
                            onChange={(value) => setAttributes({ live: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div style={{ border: '2px dashed #ccc', padding: '20px', background: '#1a1a2e', borderRadius: '4px' }}>
                        <h3 style={{ margin: '0 0 10px 0', color: '#60a5fa' }}>
                            ✨ {attributes.title}
                        </h3>
                        <p style={{ margin: 0, color: '#aaa' }}>Interactive VirtualSky star chart will display here.</p>
                        <p style={{ margin: '5px 0 0 0', fontSize: '11px', color: '#666' }}>
                            <em>Projection: {attributes.projection} | Style: {attributes.style} | Zoom: {attributes.zoom}</em>
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => null
});