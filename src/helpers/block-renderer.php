<?php

namespace ABTestingForWP;

class BlockRenderer {

    private function randomTestDistributionPosition($variants) {
        $max = array_reduce(
            $variants,
            function ($acc, $variant) {
                return $acc + $variant['distribution'];
            },
            0
        );

        return rand(1, $max);
    }

    private function pickVariantAt($variants, $number) {
        $total = 0;

        foreach ($variants as $variant) {
            $total = $total + $variant['distribution'];

            if ($number <= $total) {
                return $variant;
            }
        }

        return $variants[0];
    }

    private function getVariantContent($content, $id) {
        $doc = new \DOMDocument();
        $doc->loadHTML('<html>' . $content . '</html>');
        $xpath = new \DOMXPath($doc);

        $nodes = $xpath->query('//div[contains(@class, "' . $id . '")]');

        if (sizeof($nodes) === 0) {
            return '';
        }

        return $doc->saveXML($nodes[0]);
    }

    private function pickVariant($variants, $testId) {
        if (isset($_COOKIE['ab-testing-for-wp'])) {
            $cookieData = json_decode(stripslashes($_COOKIE['ab-testing-for-wp']), true);

            if (isset($cookieData[$testId])) {
                // make sure variant is still in varients
                foreach ($variants as $variant) {
                    if ($variant['id'] === $cookieData[$testId]) {
                        return $variant;
                    }
                }
            }
        }

        $pickedVariant = $this->pickVariantAt($variants, $this->randomTestDistributionPosition($variants));

        $abTestTracking = new ABTestTracking();
        $abTestTracking->addParticipation($pickedVariant['id']);

        $cookieData = [];
        if (isset($_COOKIE['ab-testing-for-wp'])) {
            $cookieData = json_decode(stripslashes($_COOKIE['ab-testing-for-wp']), true);
        }

        $cookieData[$testId] = $pickedVariant['id'];

        setcookie('ab-testing-for-wp', json_encode($cookieData), time() + (60*60*24*30), '/');

        return $pickedVariant;
    }

    private function getControlVariant($variants, $testId, $control) {
        // get control variant version
        foreach ($variants as $variant) {
            if ($variant['id'] === $control) {
                return $variant;
            }
        }

        // when all else fails... return the first variant
        return $variants[0];
    }

    private function wrapData($testId, $controlContent) {
        return
            '<div class="ABTestWrapper" data-test="' . $testId . '">'
                . $controlContent
            . '</div>';
    }

    public function resolveVariant($request) {
        if (!$request->get_param('test')) {
            return new \WP_Error('rest_invalid_request', 'Missing test parameter.', ['status' => 400]);
        }

        $testId = $request->get_param('test');
        $variantId = $request->get_param('variant');
        $forcedVariant = isset($variantId);

        $testManager = new ABTestManager();
        $postId = $testManager->getTestPostId($testId);

        // get contents of the post to extract gutenberg block
        $content_post = get_post($postId);
        $content = $content_post->post_content;

        // find the json data of the test in the post
        $testData = ABTestContentParser::findTestInContent($content, $testId);

        if (!$testData) {
            return new \WP_Error('rest_invalid_request', 'Could not find test data on post.', ['status' => 400]);
        }

        // extract data
        $isEnabled = isset($testData['isEnabled']) && $testData['isEnabled'];
        $variants = $testData['variants'];
        $control = $testData['control'];

        // get control variant of the test
        $controlVariant = $this->getControlVariant($variants, $testId, $control);

        // skip picking variant if provided in request
        if (!$forcedVariant) {
            // pick a variant of the test
            $pickedVariant = $isEnabled ? $this->pickVariant($variants, $testId) : $controlVariant;

            $variantId = $pickedVariant['id'];
        }

        // skip parsing HTML if control and variant not provided
        if ($variantId === $control && !$forcedVariant) {
            return rest_ensure_response([ 'id' => $variantId ]);
        }

        // parse HTML of the test and send
        $variantContent = apply_filters('the_content', $this->getVariantContent($content, $variantId));

        return rest_ensure_response([
            'id' => $variantId,
            'html' => $variantContent,
        ]);
    }

    public function renderTest($attributes, $content) {
        $variants = $attributes['variants'];
        $testId = $attributes['id'];
        $control = $attributes['control'];
        $isEnabled = isset($attributes['isEnabled']) ? $attributes['isEnabled'] : false;

        $controlVariant = $this->getControlVariant($variants, $testId, $control);

        if (!isset($controlVariant)) {
            return '';
        }

        return $this->wrapData($testId, $this->getVariantContent($content, $controlVariant['id']));
    }

    public function renderInsertedTest($attributes) {
        $content_post = get_post($attributes['id']);
        $content = $content_post->post_content;

        return apply_filters('the_content', $content);
    }

}
