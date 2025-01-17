// @flow

import React from 'react';

import { blocks, i18n, data } from '../wp';

import SVGIcon from '../components/Logo/Logo';
import Inserter from '../components/Inserter/Inserter';
import EditWrapper from '../components/TestPreview/EditWrapper';

const { registerBlockType, createBlock } = blocks;
const { __ } = i18n;
const { withDispatch } = data;

type EditProps = {
  attributes: {
    id: string;
  };
  setAttributes: (newAttributes: any) => void;
  removeSelf: () => void;
  insertNew: () => void;
};

registerBlockType('ab-testing-for-wp/ab-test-block-inserter', {
  title: __('A/B Test'),
  description: __('A/B Test inserter allows you to pick an existing test or create a new one.'),
  icon: SVGIcon,
  category: 'widgets',
  attributes: {
    id: {
      type: 'string',
      default: '',
    },
  },
  edit: withDispatch((dispatch, props) => {
    const { removeBlock } = dispatch('core/editor');
    const { clientId, insertBlocksAfter } = props;

    const removeSelf = () => removeBlock(clientId);

    return {
      insertNew() {
        insertBlocksAfter(createBlock('ab-testing-for-wp/ab-test-block'));
        removeSelf();
      },
      removeSelf,
    };
  })(({
    removeSelf,
    insertNew,
    attributes,
    setAttributes,
  }: EditProps) => {
    if (attributes.id) return <EditWrapper id={attributes.id} />;

    return (
      <Inserter
        pickTest={id => setAttributes({ id: id.toString() })}
        removeSelf={removeSelf}
        insertNew={insertNew}
      />
    );
  }),
  save() {
    return null;
  },
});
