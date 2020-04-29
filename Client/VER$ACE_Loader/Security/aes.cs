using System.Security.Cryptography;
using System.IO;
using System.Text;
using System;

namespace Security
{
    class aes
    {
        private static byte[] iv = new byte[16] { 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0 };
        private static SHA256 my_sha_256 = SHA256.Create();
        private static byte[] key = my_sha_256.ComputeHash(Encoding.ASCII.GetBytes("VER$ACE_HACK_ENCRYPTION_KEY")); 
        //not sure if this is proper ^

        public static string encrypt_string(string plain_text)
        {
            // Instantiate a new Aes object to perform string symmetric encryption
            Aes encryptor = Aes.Create();

            encryptor.Mode = CipherMode.CBC;

            // Set key and IV
            byte[] aes_key = new byte[32];
            Array.Copy(key, 0, aes_key, 0, 32);
            encryptor.Key = aes_key;
            encryptor.IV = iv;

            // Instantiate a new MemoryStream object to contain the encrypted bytes
            MemoryStream memory_stream = new MemoryStream();

            // Instantiate a new encryptor from our Aes object
            ICryptoTransform aes_encryptor = encryptor.CreateEncryptor();

            // Instantiate a new CryptoStream object to process the data and write it to the 
            // memory stream
            CryptoStream crypto_stream = new CryptoStream(memory_stream, aes_encryptor, CryptoStreamMode.Write);

            // Convert the plainText string into a byte array
            byte[] plain_bytes = Encoding.ASCII.GetBytes(plain_text);

            // Encrypt the input plaintext string
            crypto_stream.Write(plain_bytes, 0, plain_bytes.Length);

            // Complete the encryption process
            crypto_stream.FlushFinalBlock();

            // Convert the encrypted data from a MemoryStream to a byte array
            byte[] cipherBytes = memory_stream.ToArray();

            // Close both the MemoryStream and the CryptoStream
            memory_stream.Close();
            crypto_stream.Close();

            // Convert the encrypted byte array to a base64 encoded string
            string encrypted_text = Convert.ToBase64String(cipherBytes, 0, cipherBytes.Length);

            // Return the encrypted data as a string
            return encrypted_text;
        }
        public static string decrypt_string(string encrypted_text)
        {
            // Instantiate a new Aes object to perform string symmetric encryption
            Aes encryptor = Aes.Create();

            encryptor.Mode = CipherMode.CBC;

            // Set key and IV
            byte[] aesKey = new byte[32];
            Array.Copy(key, 0, aesKey, 0, 32);
            encryptor.Key = aesKey;
            encryptor.IV = iv;

            // Instantiate a new MemoryStream object to contain the encrypted bytes
            MemoryStream memory_stream = new MemoryStream();

            // Instantiate a new encryptor from our Aes object
            ICryptoTransform aes_decryptor = encryptor.CreateDecryptor();

            // Instantiate a new CryptoStream object to process the data and write it to the 
            // memory stream
            CryptoStream crypto_stream = new CryptoStream(memory_stream, aes_decryptor, CryptoStreamMode.Write);

            // Will contain decrypted plaintext
            string plain_text = String.Empty;

            try
            {
                // Convert the ciphertext string into a byte array
                byte[] encrypted_bytes = Convert.FromBase64String(encrypted_text);

                // Decrypt the input ciphertext string
                crypto_stream.Write(encrypted_bytes, 0, encrypted_bytes.Length);

                // Complete the decryption process
                crypto_stream.FlushFinalBlock();

                // Convert the decrypted data from a MemoryStream to a byte array
                byte[] plain_bytes = memory_stream.ToArray();

                // Convert the decrypted byte array to string
                plain_text = Encoding.ASCII.GetString(plain_bytes, 0, plain_bytes.Length);
            }
            finally
            {
                // Close both the MemoryStream and the CryptoStream
                memory_stream.Close();
                crypto_stream.Close();
            }

            // Return the decrypted data as a string
            return plain_text;
        }
    }
}
