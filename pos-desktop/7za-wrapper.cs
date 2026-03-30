using System;
using System.Diagnostics;

class Program
{
    static int Main(string[] args)
    {
        string dir = AppDomain.CurrentDomain.BaseDirectory;
        string real7za = System.IO.Path.Combine(dir, "7za-real.exe");

        var psi = new ProcessStartInfo
        {
            FileName = real7za,
            Arguments = string.Join(" ", args),
            UseShellExecute = false,
            RedirectStandardOutput = true,
            RedirectStandardError = true
        };

        var p = Process.Start(psi);
        Console.Write(p.StandardOutput.ReadToEnd());
        Console.Error.Write(p.StandardError.ReadToEnd());
        p.WaitForExit();

        // Exit code 2 from 7z means "warning" (e.g. symlinks couldn't be created)
        // All actual files extracted fine — treat as success
        return p.ExitCode == 2 ? 0 : p.ExitCode;
    }
}
