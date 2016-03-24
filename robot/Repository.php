<?php

/**
 * DECO Library
 * 
 * @link https://github.com/stenvala/deco-essentials
 * @copyright Copyright (c) 2016- Antti Stenvall
 * @license https://github.com/stenvala/deco-essentials/blob/master/LICENSE (MIT License)
 */

namespace deco\essentials\robot;

/**
 * Builds automatically services from repositories
 * 
 */
class Repository {

  private $cls;
  private $ref;
  private $parents = array();
  private $children = array();
  private $peers = array();

  public function __construct($cls) {
    $this->cls = $cls;
    $this->ref = new \ReflectionClass($cls);
  }

  // writes single and list services
  public function writeServices($namespace, $to) {
    $this->writeListService($namespace, $to);
    // write single service
    $name = $this->ref->getShortName();
    $traits = $this->getTraits($namespace, $name);
    $str = $this->getHeader($namespace);
    $str .= "class $name extends \\deco\\essentials\\prototypes\\mono\\Service {\n\n";
    $str .= "  // Add traits here\n\n";
    foreach ($traits as $trait) {
      $str .= "  use $trait;\n\n";
    }
    $str .= "  // Autogenerated class members. Don't modify.\n\n";
    // primary class
    $str .= "  /**\n   * @contains \\{$this->ref->getNamespaceName()}\\$name\n   */\n";
    $str .= "  protected \$$name;\n\n";
    // write parents
    foreach ($this->parents as $parent) {
      $rf = new \ReflectionClass($parent);
      $parentName = $rf->getShortName();
      $var = $parentName == $name ? 'Parent' : $parentName;
      $str .= "  /**\n   * @contains $namespace\\{$parentName}\n   */\n";
      $str .= "  protected \$$var;\n\n";
    }
    // write children
    foreach ($this->children as $child) {
      $rf = new \ReflectionClass($child);
      $childName = $rf->getShortName();
      $var = $childName == $name ? 'Children' : $child::getClassAnnotationValue('plural', "{$childName}s");
      $str .= "  /**\n   * @contains \\$namespace\\{$childName}List\n";
      $str .= "   * @singular {$childName}\n   */\n";
      $str .= "  protected \$$var;\n\n";
    }
    // peer
    foreach ($this->peers as $peer) {
      $rf = new \ReflectionClass($peer);
      $childName = $rf->getShortName();
      $str .= "  /**\n   * @contains \\$namespace\\{$childName}List\n";
      $str .= "   * @parent true   */\n";
      $str .= "  protected \$$childName;\n\n";
    }
    $str .= "}\n";
    file_put_contents("$to/$name.php", $str);
  }

  protected function writeListService($namespace, $to) {
    $basename = $this->ref->getShortName();
    $name = "{$basename}List";
    $traits = $this->getTraits($namespace, $name);
    $str = $this->getHeader($namespace);
    $str .= "/**\n * @contains \\$namespace\\$basename\n */\n";
    $str .= "class $name extends \\deco\\essentials\\prototypes\\mono\\ListOfType {\n\n";
    $str .= "  // Add traits here\n\n";
    foreach ($traits as $trait) {
      $str .= "use $trait;\n\n";
    }
    $str .= "}\n";
    file_put_contents("$to/$name.php", $str);
  }

  public function writeRestService($namespace, $to, $parent) {
    $name = $this->ref->getShortName();
    if (class_exists("\\$namespace\\$name")){
      print "Rest service '$name' already exists and is not autogenerated.\n";
      return;
    }
    // generate class
    $str = $this->getHeader($namespace);
    $str .= "class $name extends $parent {\n\n";
    
    
    $str .= "}\n";
    file_put_contents("$to/$name.php",$str);    
  }

  protected function getHeader($namespace) {
    $str = "<?php\n\n" .
        "/**" .
        "\n * DECO ROBOT GENERATED CLASS." .
        "\n * MODIFY ONLY BY ADDING TRAITS." .
        "\n * ADDED TRAITS ARE PERSISTED OVER ROBOT CODING." .
        "\n */\n";
    $str .= "namespace $namespace;\n\n";
    return $str;
  }

  protected function getTraits($namespace, $service) {
    $traits = array();
    if (class_exists("$namespace/$service")) {
      $ser = new \ReflectionClass("$namespace/$service");
      $traits = $ser->getTraits();
    }
    $default = "\\$namespace\\traits\\$service";
    if (trait_exists($default) && !in_array($default, $traits)) {
      array_push($traits, "$default");
    }
    return $traits;
  }

  public function __call($name, $arguments) {
    if (preg_match('#^add([A-Z][a-z]*)$#', $name, $matches)) {
      $var = lcfirst($matches[1]);
      $map = array('child' => 'children', 'parent' => 'parents', 'peer' => 'peers');
      $property = $map[$var];
      array_push($this->$property, $arguments[0]);
      return $this;
    }
    if (preg_match('#^get([A-Z][a-z]*)$#', $name, $matches)) {
      $var = lcfirst($matches[1]);
      return $this->$var;
    }
  }

}