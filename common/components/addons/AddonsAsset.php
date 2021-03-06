<?php

/**
 * @Author: Wang Chunsheng 2192138785@qq.com
 * @Date:   2020-03-26 09:16:19
 * @Last Modified by:   Wang chunsheng  email:2192138785@qq.com
 * @Last Modified time: 2022-02-24 11:48:14
 */

namespace common\components\addons;

use common\helpers\FileHelper;
use Yii;
use yii\web\AssetBundle;

class AddonsAsset extends AssetBundle
{
    // public $basePath = '@webroot/assetsaddons/diandi_distribution';

    // public $baseUrl = '@web/assetsaddons/diandi_distribution';

    /**
     * {@inheritdoc}
     */
    public $sourcePath = '';

    public $version;

    /**
     * {@inheritdoc}
     */
    public $css = [];
    /**
     * {@inheritdoc}
     */
    public $js = [];

    public $jsOptions = [
        'type' => 'module',
    ];

    /**
     * {@inheritdoc}
     */
    public $depends = [
        // 'yii\web\JqueryAsset',
        'common\widgets\firevue\VuemainAsset',
    ];

    public $action = '';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        global $_GPC;
        $module = Yii::$app->controller->module->id;

        if (is_dir(Yii::getAlias('@addons/'.$module))) {
            $controllerPath = Yii::$app->controller->id;
            $actionName = Yii::$app->controller->action->id;
            $this->sourcePath = sprintf('@addons/%s/assets/', trim($module));

            FileHelper::mkdirs(Yii::getAlias($this->sourcePath.$controllerPath));

            $path = Yii::getAlias($this->sourcePath.$controllerPath.'/'.$actionName.'.js');

            if (is_file($path)) {
                $this->js[] = $controllerPath.'/'.$actionName.'.js';
            }
        }

        parent::init();
    }

    public function createDemoJs($module, $controllerPath, $actionName)
    {
        global $_GPC;
        $sourcePath = sprintf('@addons/%s/assets/', trim($module));

        FileHelper::mkdirs(Yii::getAlias($sourcePath.$controllerPath));

        $path = Yii::getAlias($sourcePath.$controllerPath.'/'.$actionName.'.js');

        if (!is_file($path)) {
            $content = $this->demoJs();
            file_put_contents($path, $content, FILE_APPEND);
            $this->js[] = $controllerPath.'/'.$actionName.'.js';
        }
    }

    public function demoJs()
    {
        return <<<EOF
        new Vue({
            el: '#dd-member-index',//????????????id
            data: function () {
                return {
                    listKey:'member_id',//??????????????????
                    height:'',
                    imgShow: true,
                    downloadLoading: false,
                    list: [],//????????????
                    imageList: [],
                    listLoading: true,
                    layout: 'total, sizes, prev, pager, next, jumper',//??????????????????
                    total: 0,//????????????
                    background: true,//????????????????????????????????????
                    selectRows: '',//???????????????????????????
                    elementLoadingText: '????????????...',
                    SearchFields:{},
                    queryForm: {
                      pageNo: 1,
                      pageSize: 10,
                      title: '',
                    },
                    searchModel:'DdMemberSearch',
                    excelConfig:{//???????????????excel????????????
                      tHeader : ['member_id','group_id','level','openid','store_id','bloc_id','username','mobile','address','nickName','avatarUrl','gender','country','province','status','city','address_id','wxapp_id','verification_token','create_time','update_time','auth_key','password_hash','password_reset_token','realname','avatar','qq','vip','birthyear','constellation','zodiac','telephone','idcard','studentid','grade','zipcode','nationality','resideprovince','graduateschool','company','education','occupation','position','revenue','affectivestatus','lookingfor','bloodtype','height','weight','alipay','msn','email','taobao','site','bio','interest'
                    ],//??????????????????
                      filterVal : ['create_time','update_time','auth_key','password_hash','password_reset_token'],//?????????????????????
                      filename:'2020-11-03',//?????????????????????
                      autoWidth: 100,//??????
                      bookType: ''//??????
                    }
                }
            },
            created: function () {
                let that = this;
                console.log('????????????????????????',window.sysinfo,window.innerHeight)
                console.log('a is: ' + this.DistributionGoods,window.innerWidth)
                if(window.innerWidth<700){
                  that.layout = 'prev,pager, next'
                }
                that.init();
            },
            methods: {
              // ?????????????????????
              init(){
                let that = this;
                that.getList();
              },
              // ??????????????????
              getList(queryForm){
                let that = this;
                let pageSize = that.queryForm.pageSize,
                    pageNo = that.queryForm.pageNo,
                    searchModel = that.searchModel
                    that.listLoading = true
                let data = {
                  pageSize:pageSize,
                  page:pageNo,
                }
                
                that.\$set(data,searchModel,that.queryForm[that.searchModel]);
                console.log('????????????',data,searchModel)
                    that.\$http.post('index', data).then((response) => {
                        //??????????????????
                        if (response.data.code == 200) {
                          that.list = response.data.data.dataProvider.allModels
                          that.total = response.data.data.dataProvider.total
                        }
                        setTimeout(() => {
                          this.listLoading = false
                        }, 500)
                        return false;
                    }, (response) => {
                        //??????????????????
                        console.log(response)
                    });
        
              },
              // ??????
              onSearch() {
                let that = this
                console.log('submit!');
                let queryForm =  that.queryForm
                    queryForm[that.searchModel] = that.SearchFields
                    that.getList(queryForm)
              },
              tableSortChange() {
                const imageList = []
                this.\$refs.tableSort.tableData.forEach((item, index) => {
                  imageList.push(item.img)
                })
                this.imageList = imageList
              },
              setSelectRows(val) {
                this.selectRows = val
              },
              handleView(row){
                let that = this
                console.log(row,row[this.listKey])
                that.Popup({
                  url:'view?id='+row[this.listKey],
                  title:'??????AI555',
                  
                  openbefore: () => {
                    // ??????????????????
                    console.log('????????????')
                  }
                })
                
              },
              handleEdit(row) {
                let that = this
                that.Popup({
                  url:'update?id='+row[this.listKey],
                  title:'??????',
                  
                  openbefore: () => {
                    // ??????????????????
                    console.log('????????????')
                  }
                })
              },
              handleDelete(row) {
                let that = this
                if (row[this.listKey]) {
                  that.\$confirm('????????????????', '??????', {
                    confirmButtonText: '??????',
                    cancelButtonText: '??????',
                    type: 'warning'
                  }).then(() => {
                    that.doDelete(row[this.listKey])
                    that.getList(that.queryForm)
                    this.\$message({
                      message: '????????????',
                      type: 'success'
                    });
                  }).catch(() => {
                    this.\$message({
                      type: 'info',
                      message: '???????????????'
                    });          
                  });
                  
                } else {
                  if (this.selectRows.length > 0) {
                    const ids = this.selectRows.map((item) => item[this.listKey]).join()
                    that.\$confirm('????????????????', '??????', {
                      confirmButtonText: '??????',
                      cancelButtonText: '??????',
                      type: 'warning'
                    }).then(() => {
                      that.doDelete(ids)
                      that.getList(that.queryForm)
                      this.\$message({
                        message: '????????????',
                        type: 'success'
                      });
                    }).catch(() => {
                      this.\$message({
                        type: 'info',
                        message: '???????????????'
                      });          
                    });
                    
                  } else {
                    this.\$message.error('??????????????????')
                    return false
                  }
                }
              },
              handleSizeChange(val) {
                console.log(1)
                let that = this
        
                that.queryForm.pageSize = val
                that.getList(that.queryForm)
                
              },
              handleCurrentChange(val) {
                console.log(2)
                let that = this
                
                that.queryForm.pageNo = val
                that.getList(that.queryForm)
                
              },
              doDelete(ids){
                let that  = this
                that.\$http.post('delete', {
                  ids:ids
                }).then((response) => {
                    console.log(response)
                    //??????????????????
                    if (response.data.code == 200) {
                      
                    }
                    
                }, (response) => {
                    //??????????????????
                    console.log(response)
                });
              },
              // ??????excel
              handleDownload() {
                let that = this
                that.downloadLoading = true
                console.log('????????????',that)
                
                const list = this.list
                const data = this.global.formatJson(that.excelConfig.filterVal, list)
                that.export_json_to_excel({
                  header: that.excelConfig.tHeader,
                  data,
                  filename: that.excelConfig.filename+'.xlsx',
                  autoWidth: that.excelConfig.autoWidth,
                  bookType: that.excelConfig.bookType
                })
                this.downloadLoading = false
                
              },
          }
        })
EOF;
    }
}
