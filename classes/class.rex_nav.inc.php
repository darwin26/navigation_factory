<?php

class rex_nav {
	protected $levelDepth;
	protected $showAll;
	protected $ignoreOfflines;
	protected $hideWebsiteStartArticle;
	protected $selectedClass;
	protected $activeClass;
	protected $ulId;
	protected $ulClass;
	protected $liClass;
	protected $liIdFromMetaField;
	protected $liClassFromMetaField;
	protected $linkFromUserFunc;
	protected $hideIds;
	protected $liIdFromCategoryId;
	protected $liClassFromCategoryId;

	// old vars from rex_navigation
	var $path = array();
	var $callbacks = array();
	var $current_article_id = -1;
	var $current_category_id = -1;

	public function __construct() {
		$this->levelDepth = 3;
		$this->showAll = false;
		$this->ignoreOfflines = true;
		$this->hideWebsiteStartArticle = false;
		$this->selectedClass = 'selected';
		$this->activeClass = 'selected active';
		$this->ulId = array();
		$this->ulClass = array();
		$this->liClass = '';
		$this->liIdFromMetaField = '';
		$this->liClassFromMetaField = '';
		$this->linkFromUserFunc = '';
		$this->hideIds = array();
		$this->liIdFromCategoryId = array();
		$this->liClassFromCategoryId = array();
	}

	public function getNavigationByLevel($levelStart = 0) {
		global $REX;
		
		$navPath = explode('|', ('0' . $REX['ART'][$REX['ARTICLE_ID']]['path'][$REX['CUR_CLANG']] . $REX['ARTICLE_ID'] . '|'));

		return $this->get($navPath[$levelStart]);
	}

	public function getNavigationByCategory($categoryId) {
		return $this->get($categoryId);
	}

	public function setLevelDepth($levelDepth) {
		$this->levelDepth = $levelDepth;
	}

	public function setShowAll($showAll) {
		$this->showAll = $showAll;
	}

	public function setIgnoreOfflines($ignoreOfflines) {
		$this->ignoreOfflines = $ignoreOfflines;
	}

	public function setHideWebsiteStartArticle($hideWebsiteStartArticle) {
		$this->hideWebsiteStartArticle = $hideWebsiteStartArticle;
	}

	public function setSelectedClass($selectedClass) {
		$this->selectedClass = $selectedClass;
	}

	public function setActiveClass($activeClass) {
		$this->activeClass = $activeClass;
	}

	public function setUlId($ulId, $level = 0) {
		$this->ulId[$level] = $ulId;
	}

	public function setUlClass($ulClass, $level = 0) {
		$this->ulClass[$level] = $ulClass;
	}

	public function setLiClass($liClass) {
		$this->liClass = $liClass;
	}

	public function setLiIdFromMetaField($liIdFromMetaField) {
		$this->liIdFromMetaField = $liIdFromMetaField;
	}

	public function setLiClassFromMetaField($liClassFromMetaField) {
		$this->liClassFromMetaField = $liClassFromMetaField;
	}

	public function setLinkFromUserFunc($linkFromUserFunc) {
		$this->linkFromUserFunc = $linkFromUserFunc;
	}

	public function setHideIds($hideIds) {
		$this->hideIds = $hideIds;
	}

	public function setliIdFromCategoryId($liIdFromCategoryId) {
		$this->liIdFromCategoryId = $liIdFromCategoryId;
	}

	public function setliClassFromCategoryId($liClassFromCategoryId) {
		$this->liClassFromCategoryId = $liClassFromCategoryId;
	}

	protected function _getNavigation($categoryId) { 
		global $REX;

		static $depth = 0;
		
		if ($categoryId < 1) {
			$cats = OOCategory::getRootCategories($this->ignoreOfflines);
		} else {
			$cats = OOCategory::getChildrenById($categoryId, $this->ignoreOfflines);
		}

		$return = '';
		$ulIdAttribute = '';
		$ulClassAttribute = '';

		if (count($cats) > 0) {
			if (isset($this->ulId[$depth])) {
				$ulIdAttribute = ' id="' . $this->ulId[$depth] . '"';
			}

			if (isset($this->ulClass[$depth])) {
				$ulClassAttribute = ' class="' . $this->ulClass[$depth] . '"';
			}

			$return .= '<ul' . $ulIdAttribute . $ulClassAttribute . '>';
		}
			
		foreach ($cats as $cat) {
			if ($this->_checkCallbacks($cat, $depth)) {

				$cssClasses = '';
				$idAttribute = '';

				// default li class
				if ($this->liClass != '') {
					$cssClasses .= ' ' . $this->liClass;
				}

				// li class
				if (is_array($this->liClassFromCategoryId) && isset($this->liClassFromCategoryId[$cat->getId()])) {
					$cssClasses .= ' ' . $this->liClassFromCategoryId[$cat->getId()];
				}

				if ($this->liClassFromMetaField != '' && $cat->getValue($this->liClassFromMetaField) != '') {
					$cssClasses .= ' ' . $cat->getValue($this->liClassFromMetaField);
				}

				// li id
				if (is_array($this->liIdFromCategoryId) && isset($this->liIdFromCategoryId[$cat->getId()])) {
					$idAttribute = ' id="' . $this->liIdFromCategoryId[$cat->getId()] . '"';
				} elseif ($this->liIdFromMetaField != '' && $cat->getValue($this->liIdFromMetaField) != '') {
					$idAttribute = ' id="' . $cat->getValue($this->liIdFromMetaField) . '"';
				}

				// selected class
				if ($cat->getId() == $this->current_category_id) {
					// active menuitem
					$cssClasses .= ' ' . $this->activeClass;
				} elseif (in_array($cat->getId(), $this->path)) {
					// current menuitem
					$cssClasses .= ' ' . $this->selectedClass;
				} else {
					// do nothing
				}

				$trimmedCssClasses = trim($cssClasses);

				// build class attribute
				if ($trimmedCssClasses != '') {
					$classAttribute = ' class="' . $trimmedCssClasses . '"';
				} else {
					$classAttribute = '';
				}

				if (($this->hideWebsiteStartArticle && ($cat->getId() == $REX['START_ARTICLE_ID'])) || (in_array($cat->getId(), $this->hideIds))) {
					// do nothing
				} else {
					$depth++;
					$urlType = 0; // default

					$return .= '<li' . $idAttribute . $classAttribute . '>';

					if ($this->linkFromUserFunc != '') {
						$defaultLink = call_user_func($this->linkFromUserFunc, $cat, $depth);
					} else {
						$defaultLink = '<a href="' . $cat->getUrl() . '">' . htmlspecialchars($cat->getName()) . '</a>';
					}

					if (!class_exists('seo42')) {
						// normal behaviour
						$return .= $defaultLink;
					} else {
						// only with seo42 2.0.0+
						$urlData = seo42::getCustomUrlData($cat);

						// check if default lang has url clone option (but only if current categoy has no url data set)
						if (count($REX['CLANG']) > 1 && !isset($urlData['url_type'])) {
							$defaultLangCat = OOCategory::getCategoryById($cat->getId(), $REX['START_CLANG_ID']);
							$urlDataDefaultLang = seo42::getCustomUrlData($defaultLangCat);
				
							if (isset($urlDataDefaultLang['url_clone']) && $urlDataDefaultLang['url_clone']) {
								// clone url data from default language to current language
								$urlData = $urlDataDefaultLang;
							}
						}

						if (isset($urlData['url_type'])) {
							switch ($urlData['url_type']) { 
								case 5: // SEO42_URL_TYPE_NONE
									$return .= htmlspecialchars($cat->getName());
									break;
								case 4: // SEO42_URL_TYPE_LANGSWITCH
									$newClangId = $urlData['clang_id'];
									$newArticleId = $REX['ARTICLE_ID'];
									$catNewLang = OOCategory::getCategoryById($newArticleId, $newClangId);

									// if category that should be switched is not online, switch to start article of website
									if (OOCategory::isValid($catNewLang) && !$catNewLang->isOnline()) {
										$newArticleId = $REX['START_ARTICLE_ID'];
									}

									// select li that is current language
									if ($REX['CUR_CLANG'] == $newClangId) {
										$return = substr($return, 0, strlen($return) - strlen('<li>'));
										$return .= '<li class="' . $this->selectedClass . '">';
									}

									$return .= '<a href="' . rex_getUrl($newArticleId, $newClangId) . '">' . htmlspecialchars($cat->getName()) . '</a>';
									break;
								case 8: // SEO42_URL_TYPE_CALL_FUNC
									$return .= call_user_func($urlData['func'], $cat);
									break;
								default:
									$return .= $defaultLink;
									break;
							}
						} else {
							$return .= $defaultLink;
						}
					} 
				
					if (($this->showAll || $cat->getId() == $this->current_category_id || in_array($cat->getId(), $this->path)) && ($this->levelDepth > $depth || $this->levelDepth < 0)) {
						$return .= $this->_getNavigation($cat->getId());
					}
				
					$depth--;

					$return .= '</li>';
				}
			}
		}

		if (count($cats) > 0) {
			$return .= '</ul>';
		}

		return $return;
	}

	protected function get($categoryId = 0) { 
		if (!$this->_setActivePath()) {
			return false;
		}

		if (class_exists('rex_com_auth')) {
			$this->addCallback("nav42::checkPerm");
		}
		
		return $this->_getNavigation($categoryId);
	}

	protected function _setActivePath() {
		global $REX;

		$article_id = $REX["ARTICLE_ID"];
		
		if ($OOArt = OOArticle::getArticleById($article_id)) {
			$path = trim($OOArt->getValue("path"), '|');
			$this->path = array();

			if	($path != "") {
				$this->path = explode("|", $path);
			}

			$this->current_article_id = $article_id;
			$this->current_category_id = $OOArt->getCategoryId();
	
			return TRUE;
		}

		return FALSE;
	}

	protected function checkPerm($nav, $depth) {
		return rex_com_auth::checkPerm($nav);
	}

	public function addCallback($callback = "", $depth = "") {
		if ($callback != "") {
			$this->callbacks[] = array("callback" => $callback, "depth" => $depth);
		}
	}

	protected function _checkCallbacks($category, $depth) {
		foreach($this->callbacks as $c) {
			if ($c["depth"] == "" || $c["depth"] == $depth) {
				$callback = $c['callback'];
			
				if (is_string($callback)) {
					$callback = explode('::', $callback, 2);

					if (count($callback) < 2) {
						$callback = $callback[0];
					}
				}

				if (is_array($callback) && count($callback) > 1) {
					list($class, $method) = $callback;

					if (is_object($class)) {
						$result = $class->$method($category, $depth);
					} else {
						$result = $class::$method($category, $depth);
					}
				} else {
					$result = $callback($category, $depth);
				}

				if (!$result) {
					return false;
				}
			}
		}

		return true;
	}
}

