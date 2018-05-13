# NervSys-Study
学习NS源码

Source code: https://github.com/NervSys/NervSys

### base002

**添加demo**

> - \core\ctr\cgi.php——继承router类的cgi模式下的路由类；	
> - \demo\fruit.php——项目自定义类；	
> - \demo\conf.php——项目配置文件

**示例**
> http://host_address/api.php?cmd=demo/fruit-color-smell-guess&color=yellow&smell=sweet

demo/fruit-color-smell-guess: demo项目下 fruit 类，color, smell, guess 方法
color=yellow、smell=sweet: color、smell方法执行时的条件

### base001

**最简版本：包括**
> - api.php——入口文件；	
> - conf.php——内核配置文件；	
> - router.php——router类文件

**主要功能**

> 1. 类的自动加载
> 2. 跨域资源共享（CORS）的配置
> 3. Debug数据提示
> 4. 结果数据输出
