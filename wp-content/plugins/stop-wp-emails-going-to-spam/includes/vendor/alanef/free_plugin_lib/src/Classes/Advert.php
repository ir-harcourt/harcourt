<?php

namespace Fullworks_Free_Plugin_Lib\Classes;

class Advert {
	private function translate($text) {
		return translate($text, 'free-plugin-lib');
	}

	public function ad_display() {
		if (!$this->is_advertised_plugin_installled()) {
			?>
            <div class="fullworks-advert" role="complementary"
                 aria-label="<?php echo esc_attr($this->translate('Premium Plugin Advertisement')); ?>">
                <div class="fullworks-advert-content">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../Assets/images/anti-spam-01.png'); ?>"
                         alt="<?php echo esc_attr($this->translate('Anti-Spam Premium Plugin Features')); ?>"
                         class="fullworks-advert-image"
                         width="400"
                         height="400">
                    <div class="fullworks-advert-text">
                        <h3><?php echo esc_html($this->translate('Keep this FREE Plugin FREE')); ?></h3>
                        <h3><?php echo esc_html($this->translate('By Supporting the Developer and Upgrading to Anti-Spam Premium')); ?></h3>
                        <ul>
                            <li><?php echo esc_html($this->translate('Advanced spam protection')); ?></li>
                            <li><?php echo esc_html($this->translate('Autointegration to most Forms Packages')); ?></li>
                            <li><?php echo esc_html($this->translate('Real-time threat detection')); ?></li>
                            <li><?php echo esc_html($this->translate('Premium support')); ?></li>
                            <li><?php echo esc_html($this->translate('FREE Trial')); ?></li>
                        </ul>
                        <a href="https://fullworksplugins.com/products/anti-spam/"
                           class="button button-primary"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="<?php echo esc_attr($this->translate('Learn more about Anti-Spam Premium features')); ?>">
							<?php echo esc_html($this->translate('Show your Support & Learn More')); ?>
                        </a>
                    </div>
                </div>
            </div>
            <style>
                .fullworks-advert { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin: 20px 0; }
                .fullworks-advert-content { display: flex; align-items: center; gap: 20px; }
                .fullworks-advert-image { max-width: 400px; height: auto; }
                .fullworks-advert-text { flex: 1; }
                .fullworks-advert-text h3 { margin-top: 0; }
                .fullworks-advert-text ul { list-style-type: disc; margin-left: 20px; }
                @media (max-width: 782px) { .fullworks-advert-content { flex-direction: column; text-align: center; } }
            </style>
			<?php
		}
	}

	private function is_advertised_plugin_installled() {
		/** @var \Freemius $fwantispam_fs Freemius global object. */
		global $fwantispam_fs;
		if (null === $fwantispam_fs) {
			return false;
		}
		if ($fwantispam_fs->can_use_premium_code()) {
			return true;
		}
		return false;
	}

	public function advert_nag() {
	}
}