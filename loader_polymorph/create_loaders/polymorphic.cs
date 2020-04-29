using System;
using System.Collections.Specialized;
using System.Diagnostics;
using System.IO;
using System.Net;
using System.Reflection;
using System.Security.Cryptography;
using System.Threading;
using dnlib.DotNet;
using dnlib.DotNet.Emit;

namespace create_loaders
{
    class polymorphic
    {
        private static string username = "";

        public static void set_username(string username)
        {
            polymorphic.username = username;
        }

        private static bool contains_replaceable_str(string s)
        {
            string[] replaceable = { "[USERNAME]", "[BUILD]" };
            foreach (string replace in replaceable)
            {
                if (s.Contains(replace))
                    return true;
            }

            return false;
        }

        private static string replace_str(string s)
        {
            string modified_string = s;
            if (modified_string.Contains("[USERNAME]"))
                modified_string = modified_string.Replace("[USERNAME]", username);
            if (modified_string.Contains("[BUILD]"))
                modified_string = modified_string.Replace("[BUILD]", DateTime.Now.ToString("MM/dd/yyyy"));

            return modified_string;
        }

        public static void modify_strings(TypeDef type)
        {
            foreach (var method in type.Methods)
            {
                if (!method.HasBody) // does the method contain code?
                    continue;

                for (int i = 0; i < method.Body.Instructions.Count; i++)
                {
                    if (method.Body.Instructions[i].OpCode != OpCodes.Ldstr)
                        continue;

                    string original_string = method.Body.Instructions[i].Operand.ToString();
                    if (string.IsNullOrEmpty(original_string))
                        continue; // for some reason, we can't recover the string. Let's keep going.

                    if (!contains_replaceable_str(original_string))
                        continue;

                    var modified_str = replace_str(original_string);
                    var modified_instr = new Instruction(OpCodes.Ldstr, modified_str);

                    // ok, we've found a string to change, time to change it
                    method.Body.Instructions.RemoveAt(i); //remove original LDSTR instruction
                    method.Body.Instructions.Insert(i, modified_instr); //put our modified instruction
                    i += 1; //continue
                }
            }
        }

        private static string get_sha256(string path)
        {
            using (FileStream stream = File.OpenRead(path))
            {
                SHA256Managed sha = new SHA256Managed();
                byte[] hash = sha.ComputeHash(stream);
                return BitConverter.ToString(hash).Replace("-", String.Empty);
            }
        }

        private static int get_epoch_time()
        {
            TimeSpan t = DateTime.UtcNow - new DateTime(1970, 1, 1);
            int secondsSinceEpoch = (int)t.TotalSeconds;
            return secondsSinceEpoch;
        }

        public static void upload_file(string username, string hash)
        {
            var current_path = Path.GetDirectoryName(Assembly.GetEntryAssembly().Location);
            string[] files = Directory.GetFiles(current_path, "*.rar");
            foreach (string file in files)
            {
                //upload file because its not the other 3 files
                const string api_url = "https://versacehack.xyz/polymorphic/upload.php";
                WebClient web = new WebClient();
                web.Headers.Add("user-agent", "VER$ACE-LOADER-BOT");
                NameValueCollection post_request = new NameValueCollection
                {
                    ["client-username"] = username,
                    ["creation_time"] = get_epoch_time().ToString(),
                    ["hash"] = hash
                };
                var b64_file = Convert.ToBase64String(File.ReadAllBytes(file));
                post_request["file"] = b64_file; //file as base64
                try
                {
                    web.UploadValues(api_url, post_request);
                }
                catch
                {
                    Console.WriteLine("issue when uploading build. waiting for 5 minutes.");
                    Thread.Sleep(TimeSpan.FromMinutes(5));
                    upload_file(username, hash); //recursive function - this is dumb as hell, and I shouldn't be doing this.
                    return;
                }
            }
        }

        public static void finish_write(ModuleDefMD module, string username, int tn)
        {
            Console.WriteLine("tn[{0}] - done! writing exe.", tn);
            var directory_name = "VER$ACE_" + username;
            string modified_name = directory_name + "\\" + module.Name.Replace(".exe", "") + "_" + username + ".exe";

            Directory.CreateDirectory(directory_name);
            module.Write(modified_name);
            do_net_reactor(modified_name);
            var hash = get_sha256(modified_name);
            add_to_rar(modified_name);
            upload_file(username, hash);
        }

        public static void do_net_reactor(string old_module_name)
        {
            string args = "-file \"{0}\" -antitamp 1 -control_flow_obfuscation 1 -flow_level 9 -necrobit 1 -prejit 1";
            var path = Path.GetDirectoryName(Assembly.GetExecutingAssembly().Location);
            args = string.Format(args, path + "\\VER$ACE_" + username + "\\VER$ACE_" + username + ".exe");
            Process p = new Process
            {
                StartInfo =
                {
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    FileName = "C:\\Program Files (x86)\\Eziriz\\.NET Reactor\\dotNET_Reactor.Console.exe",
                    Arguments = args
                }
            };
            p.Start();
            p.WaitForExit();
            File.Delete(old_module_name);
            var obfuscated_path = path + "\\VER$ACE_" + username + "\\VER$ACE_Secure\\VER$ACE_" + username + ".exe";
            File.Copy(obfuscated_path, old_module_name);
            File.Delete(obfuscated_path);
            Directory.Delete(path + "\\VER$ACE_" + username + "\\VER$ACE_Secure", true);
        }

        public static void add_to_rar(string file_name)
        {
            Process cmd_to_rar = new Process
            {
                StartInfo =
                {
                    UseShellExecute = false,
                    FileName = Environment.ExpandEnvironmentVariables("%ProgramFiles%\\WinRAR\\rar.exe"),
                    Arguments = "a -pVER$ACE -r output.rar " + file_name + " Newtonsoft.Json.dll"
                }
            };
            cmd_to_rar.Start();
            cmd_to_rar.WaitForExit();
        }
    }
}
