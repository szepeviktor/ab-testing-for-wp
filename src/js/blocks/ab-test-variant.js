// @flow

import React from 'react';

import { i18n, blocks, editor } from '../wp';

import SVGIcon from '../components/Logo/Logo';

import allowedBlockTypes from '../core/allowedBlockTypes';

const { registerBlockType } = blocks;
const { __, sprintf } = i18n;
const { InnerBlocks } = editor;

type ABTestBlockChildProps = {
  attributes: ABTestVariant;
} & GutenbergProps;

function isValidContent(defaultContent: any) {
  return defaultContent && defaultContent.block && defaultContent.block.name;
}

registerBlockType('ab-testing-for-wp/ab-test-block-variant', {
  title: __('A/B Test Variant'),
  description: __('Test variant belonging to the parent A/B test container'),
  icon: SVGIcon,
  category: 'widgets',
  parent: ['ab-testing-for-wp/ab-test-block'],
  supports: {
    inserter: false,
    reusable: false,
    html: false,
  },
  attributes: {
    id: {
      type: 'string',
      default: '',
    },
    name: {
      type: 'string',
      default: '',
    },
    selected: {
      type: 'boolean',
      default: false,
    },
    defaultContent: {
      type: 'object',
      default: null,
    },
  },
  edit(props: ABTestBlockChildProps) {
    const { attributes } = props;

    const { id, name, defaultContent } = attributes;

    const template = defaultContent && isValidContent(defaultContent)
      ? [
        [defaultContent.block.name, defaultContent.attributes || {}],
      ]
      : [
        ['core/button', {
          text: sprintf(__('Button for Test Variant "%s"'), name),
        }],
        ['core/paragraph', { placeholder: sprintf(__('Enter content or add blocks for test variant "%s"'), name) }],
      ];

    return (
      <div className={`ABTestVariant ABTestVariant--${id}`}>
        <InnerBlocks
          template={template}
          templateLock={false}
          allowedBlocks={allowedBlockTypes()}
        />
      </div>
    );
  },
  save(props: ABTestBlockChildProps) {
    const { attributes } = props;

    const { id } = attributes;

    return <div className={`ABTestChild--${id}`}><InnerBlocks.Content /></div>;
  },
});
