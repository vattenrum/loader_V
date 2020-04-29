using injection_stuff;
using System;
using System.Diagnostics;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Threading;
using System.Windows.Forms;

class injection_helper
{

    private static byte[] to_inject;
    public static void start_injection_thread(byte[] cheat_file, string key)
    {
        // Pass parameters to new thread so we don't freeze GUI while decrypting file.
        Thread injection_thread = new Thread(() => v_injection_thread(cheat_file, key));
        injection_thread.Start();
    }

    private static void v_injection_thread(byte[] cheat_file, string key)
    {
        // Decrypts the currently encrypted dll to a byte array and maps it into csgo.
        to_inject = decrypt_cheat(cheat_file, key);

        int waited_for_csgo = 0;
        while (true)
        {
            Process csgo = null;
            while (csgo == null)
            {
                csgo = csgo_ready();
                Thread.Sleep(500);
                waited_for_csgo += 500;
                if (waited_for_csgo >= 300000 /* 5 minutes */)
                {
                    MessageBox.Show("Injection timed out, are you sure you have csgo open?", "Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    Environment.Exit(1);
                }
            }
            // csgo is open, wait 4.5 seconds and then try to inject
            Thread.Sleep(4500);
            // Use manual mapping to map dll into csgo
            // Credits: https://www.oldschoolhack.me/en/downloads/sourcecode/10621-c-x86-manual-map-injection
            manual_map_injector injector = new manual_map_injector(csgo) { async_injection = true };
            injector.Inject(to_inject);
            Environment.Exit(1);
        }
    }

    private static Process csgo_ready()
    {
        Process[] csgo_list = Process.GetProcessesByName("csgo");
        if (csgo_list.Length == 0)
            return null;

        Process csgo = csgo_list[0];
        int client = 0, engine = 0, server_browser = 0;
        foreach (ProcessModule module in csgo.Modules)
        {
            if (module.ModuleName == "client_panorama.dll")
                client = (int)module.BaseAddress;

            if (module.ModuleName == "engine.dll")
                engine = (int)module.BaseAddress;

            if (module.ModuleName == "serverbrowser.dll") //last one to load in
                server_browser = (int)module.BaseAddress;

            if (engine > 0 && client > 0 && server_browser > 0)
                break;
        }

        if (engine == 0 || client == 0 || server_browser == 0)
            return null;

        return csgo;
    }

    public static byte[] decrypt_cheat(byte[] cheat_file, string password)
    {
        try
        {
            byte[] password_bytes = Encoding.UTF8.GetBytes(password);
            byte[] salt = new byte[32];

            //var ms_1 = new MemoryStream(File.ReadAllBytes(cheat_file), false);
            //ms_1.Read(salt, 0, salt.Length);

            MemoryStream ms_crypt = new MemoryStream(cheat_file);
            //FileStream fs_crypt = new FileStream(cheat_file, FileMode.Open);
            ms_crypt.Read(salt, 0, salt.Length);

            RijndaelManaged AES = new RijndaelManaged {KeySize = 256, BlockSize = 128};

            var key = new Rfc2898DeriveBytes(password_bytes, salt, 50000);
            AES.Key = key.GetBytes(AES.KeySize / 8);
            AES.IV = key.GetBytes(AES.BlockSize / 8);
            AES.Padding = PaddingMode.PKCS7;
            AES.Mode = CipherMode.CFB;

            CryptoStream cs = new CryptoStream(ms_crypt, AES.CreateDecryptor(), CryptoStreamMode.Read);
            MemoryStream ms = new MemoryStream();

            int read;
            byte[] buffer = new byte[1048576];

            while ((read = cs.Read(buffer, 0, buffer.Length)) > 0)
                ms.Write(buffer, 0, read);

            //ms_1.Close();
            ms.Close();
            ms_crypt.Close();
            return ms.ToArray();
        }
        catch (Exception)
        {
            MessageBox.Show("Unable to decrypt cheat.", "decryption failed", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
        return null;
    }

}