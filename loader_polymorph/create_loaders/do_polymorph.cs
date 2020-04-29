using System;
using dnlib.DotNet;

namespace create_loaders
{
    class do_polymorph
    {
        public static void main_poly(string username, int tn)
        {
            ModuleContext mod_ctx = ModuleDef.CreateModuleContext();
            ModuleDefMD module = ModuleDefMD.Load(@"ldr_base.exe", mod_ctx);
            if (module == null)
            {
                Console.WriteLine("module is null (HOW??)");
                return;
            }

            Console.WriteLine("tn[{1}] - got module for username {0}", username, tn);

            /*
             * big credits to: https://blog.syscall.party/post/writing-a-simple-net-deobfuscator/
             * idea and some functions from ^
             */

            polymorphic.set_username(username);

            foreach (var type in module.GetTypes())
            {
                //this gives us classes, functions, functions body, etc
                polymorphic.modify_strings(type);
            }
            polymorphic.finish_write(module, username, tn);
        }
    }
}
