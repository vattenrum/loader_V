using System;
using System.Text;

namespace injection_stuff
{
    internal static class Helpers
    {
        internal static string to_string_ansi(byte[] buffer)
        {
            var sb = new StringBuilder();
            foreach (var t in buffer)
            {
                if (t == 0)
                    break;

                sb.Append((char)t);
            }

            return sb.ToString();
        }
    }
}
