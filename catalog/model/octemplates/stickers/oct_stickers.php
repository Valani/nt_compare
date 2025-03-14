<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOCTemplatesStickersOctStickers extends Model {
	public function getOCTStickers($result) {
		$oct_stickers_data = $oct_stikers = [];

		if ($this->config->get('oct_stickers_status')) {
			$oct_stickers = $this->config->get('oct_stickers_data');

			if (isset($result['special']) && (float)$result['special']) {
				$special = true;
			} else {
				$special = false;
			}

			$sticker_types = ['new', 'bestseller', 'popular', 'special', 'sold', 'ends'];

			foreach ($sticker_types as $sticker_type) {
				if ($this->isStickerAutoSet($oct_stickers, $sticker_type, $result, $special)) {
					$oct_stickers_data['stickers']["stickers_{$sticker_type}"] = $this->getStickerData($oct_stickers, $sticker_type);
				}
			}

			if (isset($result['oct_stickers']) && !empty($result['oct_stickers'])) {

				$this->load->model('tool/image');
			
				$languageId = (int)$this->config->get('config_language_id');
				
				foreach ($result['oct_stickers'] as $oct_sticker) {
					$oct_t_sticker = explode('_', $oct_sticker);
				
					$currentSticker = $oct_stickers[$oct_t_sticker[0]][$oct_t_sticker[1]] ?? null;
				
					if (is_null($currentSticker) || empty($currentSticker['status'])) {
						continue;
					}
				
					$title = $currentSticker['title'][$languageId] ?? '';
					if (empty($title)) {
						continue;
					}
				
					$description = $currentSticker['description'][$languageId] ?? false;
					$description = $description ? nl2br($description) : false;
				
					$image = $currentSticker['image'] ?? false;
					$sort = (int)$currentSticker['sort'];

					if ($image && is_file(DIR_IMAGE . $image)) {
						$image = $this->model_tool_image->resize($image, 32, 32);
					} 
				
					$oct_stickers_data['stickers']['stickers_'.$oct_t_sticker[1]] = [
						'title'         => $title,
						'description'   => $description,
						'image'         => $image,
						'sort'          => $sort,
					];
				}

			}

			$i_sort_order = [];

			if (isset($oct_stickers_data['stickers']) && !empty($oct_stickers_data['stickers'])) {
				foreach ($oct_stickers_data['stickers'] as $key => $value) {
					$i_sort_order[$key] = $value['sort'];
				}

				array_multisort($i_sort_order, SORT_ASC, $oct_stickers_data['stickers']);

				foreach ($oct_stickers_data['stickers'] as $st => $oct_stiker) {
					if (!empty($oct_stiker['image'])) {
						$oct_stikers['stickers'][$st] = array(
							'title' => $oct_stiker['title'],
							'description' => $oct_stiker['description'],
							'image' => $oct_stiker['image'],
							'sort' => $oct_stiker['sort']
						);
					} else {
						$oct_stikers['stickers'][$st] = $oct_stiker['title'];
					}
				}
			}
		}
		return $oct_stikers;
	}

	private function isStickerAutoSet($oct_stickers, $sticker_type, $result, $special) {
		if (!(isset($oct_stickers['stickers'][$sticker_type]['status']) && $oct_stickers['stickers'][$sticker_type]['status'])) {
			return false;
		}
		if (!(isset($oct_stickers['stickers'][$sticker_type]['auto']) && $oct_stickers['stickers'][$sticker_type]['auto'] == 'on')) {
			return false;
		}

		switch ($sticker_type) {
			case 'new':
				return strtotime($result['date_added']) >= strtotime("-".(int)$oct_stickers['stickers'][$sticker_type]['count']." day", time());
			case 'bestseller':
				return $this->model_catalog_product->getOCTBestSellerProducts($result['product_id']) >= (int)$oct_stickers['stickers'][$sticker_type]['count'];
			case 'popular':
				return $result['viewed'] > (int)$oct_stickers['stickers'][$sticker_type]['count'];
			case 'special':
				return $special;
			case 'sold':
				return (int)$result['quantity'] == (int)$oct_stickers['stickers'][$sticker_type]['count'];
			case 'ends':
				return ((int)$result['quantity'] <= (int)$oct_stickers['stickers'][$sticker_type]['count']) && ((int)$result['quantity'] > 0);
			default:
				return false;
		}
	}

	private function getStickerData($oct_stickers, $sticker_type) {
		return [
			'title' => $this->getStickerTitle($oct_stickers, $sticker_type),
			'sort' => $oct_stickers['stickers'][$sticker_type]['sort']
		];
	}

	private function getStickerTitle($oct_stickers, $sticker_type) {
		if ($sticker_type == 'special' && !isset($oct_stickers['stickers']['special']['view_title'])) {
			return false;
		}

		if (isset($oct_stickers['stickers'][$sticker_type]['title'][(int)$this->config->get('config_language_id')]) && !empty($oct_stickers['stickers'][$sticker_type]['title'][(int)$this->config->get('config_language_id')])) {
			return $oct_stickers['stickers'][$sticker_type]['title'][(int)$this->config->get('config_language_id')];
		} else {
			return $this->language->get("entry_sticker_{$sticker_type}");
		}
	}
}
