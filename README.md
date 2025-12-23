# FreshHarvest-Market
DI31003 Assignment 2 - Database Implementation

## 仓库使用指南
### 目录
1. 仓库的分支结构
2. 如何使用仓库
3. 如何管理分支
4. 速通：如何把新的功能/修改提交到github上   

### 1. 仓库的分支结构
> main  
>>└─ dev  
>>└─ fix  
>>└─ feature  

#### 分支说明：  
*	dev: 开发使用，由于是php前后端混合编辑，共同使用dev分支  
*	fix / feature: 隔离单个模块的开发，避免污染 dev  
    * fix：bug处理  
    * feature：新功能开发  

### 2. 如何使用GitHub远程仓库:
https://liaoxuefeng.com/books/git/remote/index.html

### 3. 如何管理分支：
https://liaoxuefeng.com/books/git/branch/index.html

### 4. 速通：如何把新的功能/修改提交到github上   
！先要保证Github知道你的公钥（见目录2）   
步骤1-7：建立新分支feature   
步骤8-11：合并到dev分支   
> **1. 本地克隆拉取**  
>   $ git clone git@github.com:你的用户名/FreshHarvest-Market.git
> 
> **2. 切换到dev分支**  
>   $ git checkout -b dev origin/dev
> 
> **3. 创建分支**  
>   （比如我需要修改readme.md，该功能代号readme-optimize）  
>   $ git switch -c feature/readme-optimize
> 
> **4. 进入你本地克隆好的文件夹，修改，修改完后**
> 
> **5. 从工作区上传**  
>   $ git add README.md  
>   
> **6. 提交**   
>   $ git commit -m "add a line to test"
> 
> **7. 同步推送到远程feature分支**  
>   $ git push -u origin feature/readme-optimize
> 
> **8. 切换到本地dev分支**  
>   （先确认功能点全部实现，本地自测无bug，代码完整后再合并）   
>   $ git switch dev   
> 
> **9. 同步远程dev最新代码**   
>   $ git pull origin dev   
>
> **10. 本地合并feature分支到dev分支**   
>   $ git merge feature/readme-optimze   
>
> **11. 推送合并好的本地dev到远程dev分支**   
>   $ git push -u origin dev   
> 
> **12. 若分支已无用，删除分支**   
>   $ git branch -d feature/readme-optimize   
>   $ git push origin --delete feature/readme-optimize
