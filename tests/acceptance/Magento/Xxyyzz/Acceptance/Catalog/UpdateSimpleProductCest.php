<?php
namespace Magento\Xxyyzz\Acceptance\Catalog;

use Magento\Xxyyzz\Step\Backend\AdminStep;
use Magento\Xxyyzz\Step\Catalog\Api\CategoryApiStep;
use Magento\Xxyyzz\Step\Catalog\Api\ProductApiStep;
use Magento\Xxyyzz\Page\Catalog\AdminProductGridPage;
use Magento\Xxyyzz\Page\Catalog\AdminProductPage;
use Magento\Xxyyzz\Page\Catalog\StorefrontCategoryPage;
use Magento\Xxyyzz\Page\Catalog\StorefrontProductPage;
use Yandex\Allure\Adapter\Annotation\Stories;
use Yandex\Allure\Adapter\Annotation\Features;
use Yandex\Allure\Adapter\Annotation\Title;
use Yandex\Allure\Adapter\Annotation\Description;
use Yandex\Allure\Adapter\Annotation\Severity;
use Yandex\Allure\Adapter\Annotation\Parameter;
use Yandex\Allure\Adapter\Model\SeverityLevel;
use Yandex\Allure\Adapter\Annotation\TestCaseId;

/**
 * Class UpdateSimpleProductCest
 *
 * Allure annotations
 * @Features({"Catalog"})
 * @Stories({"Update simple product"})
 *
 * Codeception Annotations
 * @group catalog
 * @env chrome
 * @env firefox
 * @env phantomjs
 */
class UpdateSimpleProductCest
{
    /**
     * @var array
     */
    protected $category;

    /**
     * @var array
     */
    protected $product;

    /**
     * @param AdminStep $I
     * @param CategoryApiStep $categoryApi
     * @param ProductApiStep $productApi
     */
    public function _before(AdminStep $I, CategoryApiStep $categoryApi, ProductApiStep $productApi)
    {
        $I->loginAsAdmin();
        $I->goToTheAdminProductsCatalogPage();
        
        $this->category = $I->getCategoryApiData();
        $categoryApi->amAdminTokenAuthenticated();
        $this->category = array_merge(
            $this->category,
            ['id' => $categoryApi->createCategory(['category' => $this->category])]
        );
        $this->category['url_key'] = $this->category['custom_attributes'][0]['value'];
        $this->product = $I->getSimpleProductApiData();
        $this->product['custom_attributes'][2]['value'] = $this->category['id'];
        $productApi->amAdminTokenAuthenticated();
        $this->product = array_merge(
            $this->product,
            ['id' => $productApi->createProduct(['product' => $this->product])]
        );
        if ($this->product['extension_attributes']['stock_item']['is_in_stock'] !== 0) {
            $this->product['stock_status'] = 'In Stock';
            $this->product['qty'] = $this->product['extension_attributes']['stock_item']['qty'];
        } else {
            $this->product['stock_status'] = 'Out of Stock';
        }
        $this->product['url_key'] = $this->product['custom_attributes'][0]['value'];
    }

    public function _after(AdminStep $I)
    {
        $I->goToTheAdminLogoutPage();
    }

    /**
     * Update simple product in admin.
     *
     * Allure annotations
     * @Title("Update simple product with required fields")
     * @Description("Update simple product with required fields")
     * @TestCaseId("")
     * @Severity(level = SeverityLevel::CRITICAL)
     * @Parameter(name = "Admin", value = "$I")
     * @Parameter(name = "AdminProductGridPage", value = "$adminProductGridPage")
     * @Parameter(name = "AdminProductPage", value = "$adminProductPage")
     * @Parameter(name = "StorefrontCategoryPage", value = "$storefrontCategoryPage")
     * @Parameter(name = "StorefrontProductPage", value = "$storefrontProductPage")
     *
     * @param AdminStep $I
     * @param AdminProductGridPage $adminProductGridPage
     * @param AdminProductPage $adminProductPage
     * @param StorefrontCategoryPage $storefrontCategoryPage
     * @param StorefrontProductPage $storefrontProductPage
     * @return void
     */
    public function updateSimpleProductTest(
        AdminStep $I,
        AdminProductGridPage $adminProductGridPage,
        AdminProductPage $adminProductPage,
        StorefrontCategoryPage $storefrontCategoryPage,
        StorefrontProductPage $storefrontProductPage
    ) {
        $I->wantTo('update simple product in admin.');
        $adminProductGridPage->searchBySku($this->product['sku']);
        $adminProductGridPage->seeInNthRow(1, $this->product['sku']);

        $I->wantTo('open product created from precondition.');
        $adminProductPage->amOnAdminEditProductPageById($this->product['id']);

        $I->wantTo('update product data fields.');
        $adminProductPage->fillFieldProductName($this->product['name'] . '-updated');
        $adminProductPage->fillFieldProductSku($this->product['sku'] . '-updated');
        $adminProductPage->fillFieldProductPrice($this->product['price']+10);
        $adminProductPage->fillFieldProductQuantity(
            $this->product['extension_attributes']['stock_item']['qty']+100
        );
        $I->wantTo('save product data change.');
        $adminProductPage->saveProduct();
        $adminProductPage->seeSuccessMessage();

        $I->wantTo('see updated product data.');
        $adminProductPage->amOnAdminEditProductPageById($this->product['id']);
        $adminProductPage->seeInPageTitle($this->product['name'] . '-updated');
        $adminProductPage->seeProductAttributeSet('Default');
        $adminProductPage->seeProductName($this->product['name'] . '-updated');
        $adminProductPage->seeProductSku($this->product['sku'] . '-updated');
        $adminProductPage->seeProductPrice($this->product['price']+10);
        $adminProductPage->seeProductQuantity($this->product['extension_attributes']['stock_item']['qty']+100);
        $adminProductPage->seeProductStockStatus(
            $this->product['extension_attributes']['stock_item']['is_in_stock'] !== 0 ? 'In Stock' : 'Out of Stock'
        );

        $I->wantTo('verify simple product data in frontend category page.');
        $storefrontCategoryPage->amOnCategoryPage($this->category['url_key']);
        $storefrontCategoryPage->seeProductNameInPage($this->product['name'] . '-updated');
        $storefrontCategoryPage->seeProductPriceInPage($this->product['name'] . '-updated', $this->product['price'] + 10);

        $I->wantTo('verify simple product data in frontend product page.');
        $storefrontProductPage->amOnProductPage(str_replace('_', '-', $this->product['url_key']));
        $storefrontProductPage->seeProductNameInPage($this->product['name'] . '-updated');
        $storefrontProductPage->seeProductPriceInPage($this->product['price'] + 10);
        $storefrontProductPage->seeProductSkuInPage($this->product['sku'] . '-updated');
    }
}
